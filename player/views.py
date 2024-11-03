import logging, pytz
from datetime import datetime, timedelta
from django.forms import BooleanField, CheckboxInput, CheckboxSelectMultiple, ChoiceField, Form, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone
from game.models import Game, Match
from player.forms import AcceptForm, FilterForm, FriendAddForm, FriendForm, KeenForm, BidForm, FiltersForm
from player.models import Appeal, Bid, Filter, Friend, Player, Strength
from qwikgame.constants import STRENGTH
from qwikgame.hourbits import Hours24x7
from api.models import Mark
from service.locate import Locate
from venue.models import Place, Region, Venue
from qwikgame.views import QwikView
from qwikgame.widgets import DAY_ALL, DAY_NONE, WEEK_ALL, WEEK_NONE


logger = logging.getLogger(__file__)


class FeedView(QwikView):

    def context(self, request, *args, **kwargs):
        super().context(request, *args, **kwargs)
        player = self.user.player
        feed = player.feed()
        self._context |= {
            'appeals': feed[:100],
            'feed_tab': 'selected',
            'feed_length': len(feed),
            'player': player,
            'prospects': player.prospects()[:100],
        }
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        return render(request, "player/feed.html", context)


class AcceptView(FeedView):
    accept_form_class = AcceptForm
    template_name = 'player/reply.html'

    def appeal(self, appeal_pk):
        if not appeal_pk:
            logger.warn(f'appeal_pk missing')
            return None
        appeal = Appeal.objects.filter(pk=appeal_pk).first()
        if not appeal:
            logger.warn(f'appeal missing: {appeal_pk}')
            return None
        return appeal

    def context(self, request, *args, **kwargs):
        super().context(request, *args, **kwargs)
        player = self.user.player
        appeal = self.appeal(kwargs.get('appeal'))
        if not appeal:
            return {}
        appeals = Appeal.objects.filter(player=player).all()
        prev_pk = appeals.last().pk
        next_pk = appeals.first().pk
        found = False
        for a in appeals:
            if found:
                next_pk = a.pk
                break
            if a.pk == appeal.pk:
                found = True
            else:
                prev_pk = a.pk
        replies = Bid.objects.filter(appeal=appeal).exclude(hours=None)
        friends = Friend.objects.filter(player=player)
        for reply in replies:
            reply.hour_str = reply.hours24().as_str()
            try:
                reply.name = friends.get(rival=reply.rival).name
            except:
                reply.name=reply.rival.name
        self._context |= {
            'appeal': appeal,
            'next': next_pk,
            'player_id': player.facet(),
            'prev': prev_pk,
            'replies': replies,
        }
        return self._context


    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        context |= self.accept_form_class.get()
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        player = self.user.player
        context = self.accept_form_class.post(
            request.POST,
            player,
        )
        form = context.get('accept_form')
        if form and not form.is_valid():
            context |= self.context(request, *args, **kwargs)
            return render(request, self.template_name, context)
        try:
            accept = context.get('accept')
            if accept:
                bid = Bid.objects.get(pk=accept)
                bid.log_event('accept')
                match = Match.from_bid(bid)
                bid.appeal.delete()
                match.log_event('scheduled')
                return HttpResponseRedirect(f'/game/match/{match.id}/')
            decline = context.get('decline')
            if decline:
                bid = Bid.objects.get(pk=decline_id)
                bid.log_event('decline')
                bid.delete()
        except:
            logger.exception(f'failed to process Bid: {context}')
        return HttpResponseRedirect(f'/player/feed/accept/{bid.appeal.id}/')


class BidView(FeedView):
    bid_form_class = BidForm
    template_name = 'player/bid.html'

    def appeal(self, appeal_pk):
        if not appeal_pk:
            logger.warn(f'appeal_pk missing')
            return None
        appeal = Appeal.objects.filter(pk=appeal_pk).first()
        if not appeal:
            logger.warn(f'appeal missing: {appeal_pk}')
            return None
        return appeal

    def context(self, request, *args, **kwargs):
        super().context(request, *args, **kwargs)
        player = self.user.player
        appeal = self.appeal(kwargs.get('appeal'))
        if not appeal:
            return {}
        appeals = Appeal.objects.all()
        prev_pk = appeals.last().pk
        next_pk = appeals.first().pk
        found = False
        for a in appeals:
            if found:
                next_pk = a.pk
                break
            if a.pk == appeal.pk:
                found = True
            else:
                prev_pk = a.pk
        self._context |= {
            'appeal': appeal,
            'next': next_pk,
            'player_id': player.facet(),
            'prev': prev_pk,
        }
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        appeal = context.get('appeal')
        if not appeal:
            logger.warn("BidView.get() called without appeal")
            return HttpResponseRedirect("/player/feed/")
        context |= self.bid_form_class.get(appeal)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        appeal = self.appeal(kwargs.get('appeal'))
        if not appeal:
            logger.warn("BidView.get() called without appeal")
            return HttpResponseRedirect("/player/feed/")
        player = self.user.player
        context = self.bid_form_class.post(
            request.POST,
            appeal,
        )
        form = context.get('bid_form')
        if form and not form.is_valid():
            context |= self.context(request, *args, **kwargs)
            return render(request, self.template_name, context)
        bid = Bid(
            appeal=context['accept'],
            hours=context['hours'],
            rival=player,
            strength='m',
            )
        bid.save()
        bid.log_event('bid')
        return HttpResponseRedirect(f'/player/feed/{appeal.id}/')


class FilterView(FeedView):
    filter_form_class = FilterForm
    hide=[]
    template_name = 'player/filter.html'

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = super().context(request, *args, **kwargs)
        player = self.user.player
        context |= self.filter_form_class.get(
            player,
            game='ANY',
            place='ANY',
            hours=WEEK_NONE,
        )
        return render(request, self.template_name, context)


    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)     
        player = self.user.player
        context = self.filter_form_class.post(
            request.POST,
            player,
        )
        form = context.get('filter_form')
        if form and not form.is_valid():
            context |= super().context(request, *args, **kwargs)
            return render(request, self.template_name, context)
        game, place, venue = None, None, None
        placeid = context.get('placeid')
        if placeid:
            place = Place.objects.filter(placeid=placeid).first()
            if place:
                if place.is_venue:
                    venue = place.venue
            else:  # then it must be a new Venue from a google POI
                venue = Venue.from_placeid(placeid)
                if venue:
                    venue.save()
                    logger.info(f'Venue new: {venue}')
                    place = venue.place_ptr
                else:
                    logger.warn(f'Failed to create new Venue: {placeid}')
        gameid = context.get('game')
        game = Game.objects.filter(pk=gameid).first()
        # add this Game to a Venue if required
        if game and venue and not(game in venue.games.all()):
            venue.games.add(game)
            logger.info(f'Venue add Game: {game}')
            venue.save()
            mark = Mark(game=game, place=place, size=1)
            mark.save()
            logger.info(f'Mark new {mark}')
        try:
            new_filter = Filter.objects.get_or_create(
                player=self.user.player,
                game=game,
                place=place)
            filter_hours = Hours24x7(context['hours'])
            new_filter[0].set_hours(filter_hours)
            new_filter[0].save()
            logger.info(f'Filter new: {new_filter[0]}')
            # update the Mark size
            mark = Mark.objects.filter(game=game, place=place).first()
            if mark:
                mark.save()
        except:
            logger.exception("failed to add filter")
        return HttpResponseRedirect("/player/feed/filters")


class FiltersView(FeedView):
    filters_form_class = FiltersForm
    template_name = 'player/filters.html'

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = super().context(request, *args, **kwargs)
        context |= self.filters_form_class.get(self.user.player)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)     
        player = self.user.player
        context = self.filters_form_class.post(
            request.POST,
            player,
        )
        form = context.get('filters_form')
        if form and not form.is_valid():
            context |= super().context(request, *args, **kwargs)
            return render(request, self.template_name, context)
        logger.warn(context)
        activate = context.get('ACTIVATE', [])
        logger.info(f'activating filters {activate}')
        for filter in Filter.objects.filter(player=player):
            try:
                filter.active = str(filter.id) in activate
                filter.save()
            except:
                logger.exception('failed to activate filter: {} : {}'.format(player, filter.id))
        for filter_code in context.get('DELETE', []):
            try:
                junk = Filter.objects.get(pk=filter_code)
                logger.info(f'Deleting filter: {junk}')
                junk.delete()
            except:
                logger.exception('failed to delete filter: {} : {}'.format(player, filter_code))
        return HttpResponseRedirect("/player/feed/filters")


class KeenView(FeedView):
    keen_form_class = KeenForm
    template_name = 'player/keen.html'

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = super().context(request, *args, **kwargs)
        player = self.user.player
        context |= self.keen_form_class.get(player)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        player = self.user.player 
        context = self.keen_form_class.post(
            request.POST,
            player,
        )
        form = context.get('keen_form')
        if form and not form.is_valid():
            context |= super().context(request, *args, **kwargs)
            return render(request, self.template_name, context)
        game, place, venue = None, None, None
        placeid = context.get('placeid')
        if placeid:
            place = Place.objects.filter(placeid=placeid, venue__isnull=False).first()
            if place:
                venue = place.venue
            else:  # then it must be a new Venue from a google POI
                venue = Venue.from_placeid(placeid)
                if venue:
                    venue.save()
                    logger.info(f'Venue new: {venue}')
                    place = venue.place_ptr
        if not venue:
            logger.warn(f'Venue missing from Appeal: {placeid}')
            return HttpResponseRedirect('/player/feed/')
        gameid = context.get('game')
        game = Game.objects.filter(pk=gameid).first()
        if not game:
            logger.warn(f'Game missing from Appeal: {game}')
            return HttpResponseRedirect('/player/feed/')
        # add this Game to a Venue if required
        if not(game in venue.games.all()):
            venue.games.add(game)
            logger.info(f'Venue Game add: {game}')
            venue.save()
            mark = Mark(game=game, place=place, size=1)
            mark.save()
            logger.info(f'Mark new {mark}')
        appeal_pk = None
        # create/update/delete today appeal
        now=venue.now()
        venue_hours = venue.hours_open()
        appeal = Appeal.objects.get_or_create(
            date=now.date(),
            game=game,
            player=player,
            venue=venue,
        )[0]
        if context['today'].is_none():
            appeal.delete()
        elif appeal.hours24 != context['today']:
            appeal.set_hours(venue_hours & context['today'])
            appeal.log_event('keen')
            appeal.log_event('appeal')
            appeal.perish()
            appeal_pk = appeal.pk
        # create/update/delete tomorrow appeal
        one_day=datetime.timedelta(days=1)
        appeal = Appeal.objects.get_or_create(
            date=(now + one_day).date(),
            game=game,
            player=player,
            venue=venue,
        )[0]
        if context['tomorrow'].is_none():
            appeal.delete()
        elif appeal.hours24 != context['tomorrow']:
            appeal.set_hours(venue_hours & context['tomorrow'])
            appeal.log_event('keen')
            appeal.log_event('appeal')
            appeal.save()
            if not appeal_pk:
                appeal_pk = appeal.pk
        if appeal_pk:
            return HttpResponseRedirect(f'/player/feed/accept/{appeal_pk}/')
        return HttpResponseRedirect('/player/feed/')        


class InvitationView(FeedView):
    # invitation_form_class = InvitationForm
    template_name = 'game/invitation.html'

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = super().context(request, *args, **kwargs)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        # context = self.invitation_form_class.post(
        #     request.POST,
        #     self.user.player,
        # )
        if len(context) == 0:
            return HttpResponseRedirect("/player/feed/")
        context |= super().context(request, *args, **kwargs)
        return render(request, self.template_name, context)


class RivalView(FeedView):

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = super().context(request, *args, **kwargs)
        context |= { 'rate_tab': 'selected' }
        return render(request, "player/rival.html", context)


class FriendsView(QwikView):
    template_name = 'player/friends.html'

    def context(self, request, *args, **kwargs):        
        kwargs['items'] = Friend.objects.filter(player=self.user.player)
        if kwargs['items'].first():
            kwargs['pk'] = kwargs.get('friend', kwargs['items'].first().pk)
        logger.warn(kwargs['pk'])
        super().context(request, *args, **kwargs)
        self._context |= {
            'friend': self._context['item'],
            'friends': self._context['items'].order_by('name'),
            'friend_tab': 'selected',
            'player_id': self.user.player.facet(),
            'target': 'friend',
        }
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        friend = kwargs.get('item')
        if not friend: 
            return render(request, self.template_name, context)


class FriendAddView(FriendsView):
    form_class = FriendAddForm
    template_name = 'player/friend_add.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        context |= self.form_class.get()
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        context |= self.form_class.post(request.POST)
        form = context.get('form')
        if form and not form.is_valid():
            return render(request, self.template_name, context)
        try:
            email = context['email']
            email_hash = Player.hash(email)
            rival, created = Player.objects.get_or_create(email_hash = email_hash)
            friend = Friend.objects.create(
                email = email,
                name = context['name'],
                player = self.user.player,
                rival = rival,
            )
        except:
            logger.exception(f'failed add friend: {context}')
        return HttpResponseRedirect(f'/player/friend/{friend.pk}/')



class FriendView(FriendsView):
    form_class = FriendForm
    template_name = 'player/friend.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        self._context |= {
            'strengths': context['friend'].strengths.all(),
        }
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        context |= self.form_class.get()
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        context = self.form_class.post(request.POST)
        form = context.get('form')
        if form and not form.is_valid():
            return render(request, self.template_name, context)
        friend_pk = kwargs.get('friend')
        try:
            player = self.user.player
            friend = Friend.objects.get(pk=friend_pk)
            game = Game.objects.get(pk=context['game'])
            strength = Strength.objects.create(
                date = datetime.now(pytz.utc),
                game = game,
                player = player,
                rival = friend.rival, 
                relative = context['strength'],
                weight = 3,
                )
            friend.strengths.add(strength)
        except:
            logger.exception(f'failed add strength: {context}')
        context |= self.context(request, *args, **kwargs)
        return HttpResponseRedirect(f'/player/friend/{friend_pk}/')
