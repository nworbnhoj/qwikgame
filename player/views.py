import datetime, logging
from django.forms import BooleanField, CheckboxInput, CheckboxSelectMultiple, ChoiceField, Form, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone
from player.forms import AcceptForm, FilterForm, KeenForm, BidForm, FiltersForm
from player.models import Appeal, Bid, Filter, Friend
from qwikgame.constants import STRENGTH
from qwikgame.hourbits import Hours24x7
from api.models import Mark
from service.locate import Locate
from venue.models import Region, Venue
from qwikgame.views import QwikView
from qwikgame.widgets import DAY_ALL, DAY_NONE, WEEK_ALL, WEEK_NONE


logger = logging.getLogger(__file__)


class FeedView(QwikView):

    def context(self, request, *args, **kwargs):
        super().context(request, *args, **kwargs)
        player = self.user.player
        self._context |= {
            'appeals': player.feed()[:100],
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
        return HttpResponseRedirect("/game/match/{}/".format(kwargs['appeal']))


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
        return HttpResponseRedirect("/player/feed")


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
            venue='ANY',
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
        venue = context.get('venue')
        if not venue:
            placeid = context.get('placeid')
            if placeid:
                venue = Venue.from_placeid(placeid)
                if venue:
                    venue.save()
                    logger.info(f'Venue new: {venue}')
        game = context.get('game')
        if game and venue and not(game in venue.games.all()):
            venue.games.add(game)
            logger.info(f'Venue Game add: {game}')
            venue.save()
            mark = Mark(game=game, venue=venue, size=1)
            mark.save()
            logger.info(f'Mark new {mark}')
        try:
            new_filter = Filter.objects.get_or_create(
                player=self.user.player,
                game=game,
                venue=venue)
            filter_hours = Hours24x7(context['hours'])
            if venue:
                venue_hours = venue.hours_open()
                if venue_hours:
                    filter_hours = filter_hours & venue_hours
            new_filter[0].set_hours(filter_hours)
            new_filter[0].save()
            logger.info(f'Filter new: {new_filter[0]}')
            # update the Mark size
            mark = Mark.objects.filter(game=game, venue=venue).first()
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
        context = self.filters_form_class.post(
            request.POST,
            self.user.player,
        )
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
        venue = context.get('venue')
        if not venue:
            placeid = context.get('placeid')
            if placeid:
                venue = Venue.from_placeid(placeid)
                if venue:
                    venue.save()
                    logger.info(f'Venue new: {venue}')
            else:
                logger.warn("failed to create venue from placeid: {placeid}")
                return HttpResponseRedirect('/player/feed/')

        appeal_pk = None
        # create/update/delete today appeal
        now=timezone.now()
        appeal = Appeal.objects.get_or_create(
            date=now.date(),
            game=context['game'],
            player=player,
            venue=venue,
        )[0]
        if context['today'].is_none():
            appeal.delete()
        elif appeal.hours24 != context['today']:
            appeal.set_hours(context['today'])
            appeal.log_event('keen')
            appeal.log_event('appeal')
            appeal.save()
            appeal_pk = appeal.pk
        # create/update/delete tomorrow appeal
        one_day=datetime.timedelta(days=1)
        appeal = Appeal.objects.get_or_create(
            date=(now + one_day).date(),
            game=context['game'],
            player=player,
            venue=venue,
        )[0]
        if context['tomorrow'].is_none():
            appeal.delete()
        elif appeal.hours24 != context['tomorrow']:
            appeal.set_hours(context['tomorrow'])
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
        return render(request, "player/rival.html", context)