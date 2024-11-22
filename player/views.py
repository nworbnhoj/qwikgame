import logging, pytz
from datetime import datetime, timedelta
from django.forms import BooleanField, CheckboxInput, CheckboxSelectMultiple, ChoiceField, Form, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone
from game.models import Game, Match
from player.forms import AcceptForm, FilterForm, FriendForm, KeenForm, BidForm, FiltersForm, StrengthForm
from player.models import Appeal, Bid, Filter, Friend, Player, Strength
from qwikgame.constants import STRENGTH
from qwikgame.hourbits import Hours24x7
from api.models import Mark
from service.locate import Locate
from venue.models import Place, Region, Venue
from qwikgame.views import QwikView
from qwikgame.widgets import DAY_ALL, DAY_NONE, WEEK_ALL, WEEK_NONE


logger = logging.getLogger(__file__)


class AppealsView(QwikView):
    template_name = 'player/appeals.html'

    def context(self, request, *args, **kwargs):
        player = self.user.player
        appeals = player.appeals()
        kwargs['items'] = appeals
        if kwargs['items'].first():
            kwargs['pk'] = kwargs.get('appeal')
        super().context(request, *args, **kwargs)
        participate = player.appeal_participate()
        participate_list = list(participate)
        participate_list.sort(key=lambda x: x.last_hour, reverse=True)
        participate_list.sort(key=lambda x: x.date)
        for appeal in participate_list:
            seen = player.pk in appeal.meta.get('seen', [])
            appeal.seen = '' if seen else 'unseen'
        appeals = appeals.exclude(pk__in=participate)
        appeals_list = list(appeals)  
        appeals_list.sort(key=lambda x: x.last_hour, reverse=True)
        appeals_list.sort(key=lambda x: x.date)
        for appeal in appeals_list:
            seen = player.pk in appeal.meta.get('seen', [])
            appeal.seen = '' if seen else 'unseen'
        self._context |= {  
            'appeal': self._context.get('item'),
            'appeals': appeals_list[:100],
            'appeals_tab': 'selected',
            'appeals_length': len(appeals_list),
            'filtered': Filter.objects.filter(player=player, active=True).exists(),
            'player': player,
            'prospects': participate[:100],
        }
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        appeal = context.get('appeal')
        if not appeal: 
            return render(request, self.template_name, context)


class AcceptView(AppealsView):
    accept_form_class = AcceptForm
    template_name = 'player/reply.html'

    def context(self, request, *args, **kwargs):
        super().context(request, *args, **kwargs)
        player = self.user.player
        appeal = self._context.get('appeal')
        if appeal:
            bids = Bid.objects.filter(appeal=appeal).exclude(hours=None)
            friends = Friend.objects.filter(player=player)
            for bid in bids:
                bid.hour_str = bid.hours24().as_str()
                bid.name = player.name_rival(bid.rival)


                
            self._context |= {
                'player_id': player.facet(),
                'bids': bids,
                'target': 'bid',
            }
        return self._context


    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        appeal = self._context.get('appeal')
        # mark this Appeal seen by this Player
        appeal.mark_seen([self.user.player.pk]).save()
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
        if 'CANCEL' in context:
            try:
                cancel_pk = context.get('CANCEL')
                appeal = Appeal.objects.get(pk=cancel_pk)
                game = appeal.game
                venue = appeal.venue
                appeal.meta['seen'] = [player.pk]
                # mark this Appeal seen by this Player only
                appeal.meta['seen'] = [player.pk]
                appeal.save()
                logger.info(f'Cancelling Appeal: {appeal}')
                appeal.delete()
                # update the Mark size
                mark = Mark.objects.filter(game=game, place=venue).first()
                if mark:
                    mark.save()
            except:
                logger.exception('failed to cancel appeal: {} : {}'.format(player, cancel_pk))
            return HttpResponseRedirect(f'/player/appeal/')
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
            appeal = self._context.get('appeal')
            # mark this Appeal seen by this Player only
            appeal.meta['seen'] = [player.pk]
            appeal.save()
            mark = Mark.objects.filter(game=appeal.game, place=appeal.venue).first()
            if mark:
                mark.save()
        except:
            logger.exception(f'failed to process Bid: {context}')
        return HttpResponseRedirect(f'/player/appeal/accept/{bid.appeal.id}/')


class BidView(AppealsView):
    bid_form_class = BidForm
    template_name = 'player/bid.html'

    def context(self, request, *args, **kwargs):
        super().context(request, *args, **kwargs)
        bid = Bid.objects.filter(
            appeal = self._context.get('appeal'),
            rival = self.user.player,
        ).first()
        self._context |= {
            'player_id': self.user.player.facet(),
            'bid': bid,
        }
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        appeal = self._context.get('appeal')
        # mark this Appeal seen by this Player
        appeal.mark_seen([self.user.player.pk]).save()
        # redirect if this Player owns the Appeal
        if appeal.player == self.user.player:
            return HttpResponseRedirect(f'/player/appeal/accept/{appeal.id}/')
        context |= self.bid_form_class.get(context.get('appeal'))
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        player = self.user.player
        appeal = context.get('appeal')
        context |= self.bid_form_class.post(
            request.POST,
            appeal,
        )
        form = context.get('bid_form')
        if form and not form.is_valid():
            return render(request, self.template_name, context)
        if 'CANCEL' in context:
            try:
                cancel_pk = context.get('CANCEL')
                logger.warn(cancel_pk)
                bid = Bid.objects.get(pk=cancel_pk)
                # mark this Appeal seen by this Player only
                appeal.meta['seen'] = [player.pk]
                appeal.save()
                logger.info(f'Cancelling Bid: {bid}')
                bid.log_event('withdraw')
                appeal_pk = bid.appeal.pk
                bid.delete()
            except:
                logger.exception('failed to cancel bid: {} : {}'.format(player, cancel_pk))
            return HttpResponseRedirect(f'/player/appeal/{appeal.pk}/')
        bid = Bid(
            appeal=context['accept'],
            hours=context['hours'],
            rival=player,
            strength='m',
            )
        bid.save()
        bid.log_event('bid')
        # mark this Appeal seen by this Player only
        appeal.meta['seen'] = [player.pk]
        appeal.save()
        # update the Mark size
        mark = Mark.objects.filter(game=appeal.game, place=appeal.venue).first()
        if mark:
            mark.save()
        return HttpResponseRedirect(f'/player/appeal/{appeal.id}/')


class FilterView(AppealsView):
    filter_form_class = FilterForm
    hide=[]
    template_name = 'player/filter.html'

    def context(self, request, *args, **kwargs):
        super().context(request, *args, **kwargs)
        is_admin = self.user.is_admin
        self._context |= {
            'onclick_place_marker': 'select' if is_admin else 'noop',
            'onclick_region_marker': 'select',
            'onclick_search_marker': 'select',
            'onclick_venue_marker': 'select',
            'onhover_place_marker': 'info',
            'onhover_region_marker': 'info',
            'onhover_search_marker': 'info',
            'onhover_venue_marker': 'info',
            'show_search_box': 'SHOW' if is_admin else 'HIDE',
        }
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
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
        return HttpResponseRedirect("/player/appeal/filters")


class FiltersView(AppealsView):
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
        if 'ACTIVATE' in context:
            activate = context.get('ACTIVATE', [])
            logger.info(f'activating filters {activate}')
            for filter in Filter.objects.filter(player=player):
                try:
                    filter.active = str(filter.id) in activate
                    filter.save()
                except:
                    logger.exception('failed to activate filter: {} : {}'.format(player, filter.id))
            return HttpResponseRedirect("/player/appeal/")
        if 'DELETE' in context:
            delete = context.get('DELETE', [])
            logger.info(f'deleting filters {delete}')
            for filter_code in delete:
                try:
                    junk = Filter.objects.get(pk=filter_code)
                    logger.info(f'Deleting filter: {junk}')
                    junk.delete()
                except:
                    logger.exception('failed to delete filter: {} : {}'.format(player, filter_code))
        return HttpResponseRedirect("/player/appeal/filters")


class KeenView(AppealsView):
    keen_form_class = KeenForm
    template_name = 'player/keen.html'

    def context(self, request, *args, **kwargs):
        super().context(request, *args, **kwargs)
        is_admin = self.user.is_admin
        self._context |= {
            'onclick_place_marker': 'select' if is_admin else 'noop',
            'onclick_region_marker': 'center',
            'onclick_search_marker': 'select',
            'onclick_venue_marker': 'select',
            'onhover_place_marker': 'info',
            'onhover_region_marker': 'info',
            'onhover_search_marker': 'info',
            'onhover_venue_marker': 'info',
            'show_search_box': 'SHOW' if is_admin else 'HIDE',
        }
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
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
            return HttpResponseRedirect('/player/appeal/')
        gameid = context.get('game')
        game = Game.objects.filter(pk=gameid).first()
        if not game:
            logger.warn(f'Game missing from Appeal: {game}')
            return HttpResponseRedirect('/player/appeal/')
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
        today=venue.now()
        appeal, created = Appeal.objects.get_or_create(
            date=today.date(),
            game=game,
            player=player,
            venue=venue,
        )
        valid_hours = venue.open_date(today) & context['today']
        if valid_hours.is_none():
            appeal.delete()
            logger.info(f"no valid hours for {context['today']} {today} at {venue}")
        elif appeal.hours24 != context['today']:
            appeal.set_hours(valid_hours)
            logger.info(f'update Appeal: {appeal}')
            appeal.log_event('keen')
            appeal.log_event('appeal')
            appeal.perish()
            appeal_pk = appeal.pk
        # create/update/delete tomorrow appeal
        tomorrow = today + timedelta(days=1)
        appeal, created = Appeal.objects.get_or_create(
            date=tomorrow.date(),
            game=game,
            player=player,
            venue=venue,
        )
        valid_hours = venue.open_date(tomorrow) & context['tomorrow']
        if valid_hours.is_none():
            appeal.delete()
            logger.info(f"no valid hours for {context['today']} {tomorrow} at {venue}")
        elif appeal.hours24 != context['tomorrow']:
            appeal.set_hours(valid_hours)
            logger.info(f'update Appeal: {appeal}')
            appeal.log_event('keen')
            appeal.log_event('appeal')
            appeal.save()
            if not appeal_pk:
                appeal_pk = appeal.pk
        # update the Mark size
        mark = Mark.objects.filter(game=game, place=place).first()
        if mark:
            mark.save()
        if appeal_pk:
            return HttpResponseRedirect(f'/player/appeal/{appeal_pk}/')
        else:
            return HttpResponseRedirect(f'/player/appeal/')


class InvitationView(AppealsView):
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
            return HttpResponseRedirect("/player/appeal/")
        context |= super().context(request, *args, **kwargs)
        return render(request, self.template_name, context)


class RivalView(AppealsView):

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = super().context(request, *args, **kwargs)
        context |= { 'review_tab': 'selected' }
        return render(request, "player/rival.html", context)


class FriendsView(QwikView):
    template_name = 'player/friends.html'

    def context(self, request, *args, **kwargs):        
        kwargs['items'] = Friend.objects.filter(player=self.user.player).order_by('name')
        if kwargs['items'].first():
            kwargs['pk'] = kwargs.get('friend')
        super().context(request, *args, **kwargs)
        self._context |= {
            'friend': self._context.get('item'),
            'friends': self._context.get('items'),
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
    form_class = FriendForm
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
    friend_form_class = FriendForm
    strength_form_class = StrengthForm
    template_name = 'player/friend.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        friend = context.get('friend')
        if friend:
            self._context['strengths'] = friend.strengths.all()
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        friend = context.get('friend')
        strength = friend.strengths.first() if friend else None
        context |= self.friend_form_class.get(friend)
        context |= self.strength_form_class.get(strength)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        context = self.friend_form_class.post(request.POST)
        context |= self.strength_form_class.post(request.POST)
        friend_form = context.get('friend_form')
        if friend_form and not friend_form.is_valid():
            return render(request, self.template_name, context)
        strength_form = context.get('strength_form')
        if strength_form and not strength_friend_form.is_valid():
            return render(request, self.template_name, context)
        player = self.user.player
        friend_pk = kwargs.get('friend')
        if 'DELETE' in context:
            try:
                delete_pk = context.get('DELETE')
                junk = Friend.objects.get(pk=delete_pk)
                logger.info(f'Deleting friend: {junk}')
                junk.delete()
            except:
                logger.exception('failed to delete friend: {} : {}'.format(player, delete_pk))
            return HttpResponseRedirect(f'/player/friend/')
        if 'DELETE_STRENGTH' in context:
            try:
                delete_pk = context.get('DELETE_STRENGTH')
                junk = Strength.objects.get(pk=delete_pk)
                logger.info(f'Deleting strength: {junk}')
                junk.delete()
            except:
                logger.exception('failed to delete strength: {} : {}'.format(player, delete_pk))
            return HttpResponseRedirect(f'/player/friend/{friend_pk}/')
        email = context.get('email')
        name = context.get('name')
        if friend_pk:    # modifying an existing friend
            try:
                friend = Friend.objects.get(pk=friend_pk)
                if email != friend.email:
                    friend.email = email
                    email_hash = Player.hash(email)
                    strengths = friend.strengths.all()
                    rival, created = Player.objects.get_or_create(email_hash = email_hash)
                    friend.rival = rival
                    friend.save()
                    for strength in strengths:
                        strength.rival = rival
                        strength.save()
                    friend_pk = friend.pk
                if name != friend.name:
                    friend.name = name
                    friend.save()
            except:
                logger.exception(f'failed modify friend: {context}')
        else:    # creating a new friend
            try:
                email_hash = Player.hash(email)
                rival, created = Player.objects.get_or_create(email_hash = email_hash)
                friend = Friend.objects.create(
                    email = email,
                    name = context['name'],
                    player = player,
                    rival = rival,
                )
                friend_pk = friend.pk
            except:
                logger.exception(f'failed add friend: {context}')
        try:    # 
            friend = Friend.objects.get(pk=friend_pk)
            game = Game.objects.get(pk=context['game'])
            strength = Strength.objects.filter(
                game = game,
                player = player,
                rival = friend.rival,
                ).first()
            if strength:
                strength.date = datetime.now(pytz.utc)
                strength.relative = context['strength']
                strength.weight = 3
                strength.save()
            else:                
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




class FriendStrengthView(FriendsView):
    form_class = StrengthForm
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
