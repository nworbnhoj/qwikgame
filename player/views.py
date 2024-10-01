import datetime, logging
from django.forms import BooleanField, CheckboxInput, CheckboxSelectMultiple, ChoiceField, Form, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone
from player.forms import AcceptForm, FilterForm, KeenForm, BidForm, FiltersForm
from player.models import Appeal, Bid, Filter, Friend
from qwikgame.constants import STRENGTH
from qwikgame.hourbits import Hours24x7
from api.models import Region, Mark
from service.locate import Locate
from venue.models import Venue
from qwikgame.views import QwikView
from qwikgame.widgets import DAY_ALL, DAY_NONE, WEEK_ALL, WEEK_NONE

logger = logging.getLogger(__file__)

class FilterView(QwikView):
    filter_form_class = FilterForm
    hide=[]
    template_name = 'player/filter.html'

    def get(self, request, *args, **kwargs):
        super().get(request)
        player = self.user.player
        context = self.filter_form_class.get(
            player,
            game='ANY',
            venue='ANY',
            hours=WEEK_NONE,
        )
        context |= {
            'appeals': Appeal.objects.all(),
            'bids': Bid.objects.filter(rival=player).all(),
        }
        context |= super().context(request)
        return render(request, self.template_name, context)


    def post(self, request, *args, **kwargs):
        super().post(request)
        context = self.filter_form_class.post(
            request.POST,
        )

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


class FeedView(QwikView):

    def get(self, request, *args, **kwargs):
        super().request_init(request)
        player = self.user.player
        context = {
            'appeals': Appeal.objects.all(),
            'bids': Bid.objects.filter(rival=player).all(),
        }
        context |= super().context(request)
        return render(request, "player/feed.html", context)


class KeenView(QwikView):
    keen_form_class = KeenForm
    template_name = 'player/keen.html'

    def get(self, request, *args, **kwargs):
        super().get(request)
        player = self.user.player
        context = {
            'appeals': Appeal.objects.all(),
            'bids': Bid.objects.filter(rival=player).all(),
        }
        context |= self.keen_form_class.get(player)
        context |= super().context(request)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        player = self.user.player 
        context = self.keen_form_class.post(
            request.POST,
            player,
        )
        if 'keen_form' in context: 
            context |= {
                'appeals': Appeal.objects.all(),
                'bids': Bid.objects.filter(rival=player).all(),
            }
            return render(request, self.template_name, context)
        # create/update/delete today appeal
        now=timezone.now()
        appeal = Appeal.objects.get_or_create(
            date=now.date(),
            game=context['game'],
            player=player,
            venue=context['venue'],
        )[0]
        if context['today'].is_none():
            appeal.delete()
        elif appeal.hours24 != context['today']:
            appeal.set_hours(context['today'])
            appeal.log_event('keen')
            appeal.log_event('appeal')
            appeal.save()
        # create/update/delete tomorrow appeal
        one_day=datetime.timedelta(days=1)
        appeal = Appeal.objects.get_or_create(
            date=(now + one_day).date(),
            game=context['game'],
            player=player,
            venue=context['venue'],
        )[0]
        if context['tomorrow'].is_none():
            appeal.delete()
        elif appeal.hours24 != context['tomorrow']:
            appeal.set_hours(context['tomorrow'])
            appeal.log_event('keen')
            appeal.log_event('appeal')
            appeal.save()
        return HttpResponseRedirect(f'/player/feed/replys/{appeal.id}/')


class InvitationView(QwikView):
    # invitation_form_class = InvitationForm
    template_name = 'game/invitation.html'

    def get(self, request, *args, **kwargs):
        super().get(request)
        player = self.user.player
        context = {
            'appeals': Appeal.objects.all(),
            'bids': Bid.objects.filter(rival=player).all(),
        }
        context |= super().context(request)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        # context = self.invitation_form_class.post(
        #     request.POST,
        #     self.user.player,
        # )
        if len(context) == 0:
            return HttpResponseRedirect("/player/feed/")
        context |= super().context(request)
        return render(request, self.template_name, context)


class ReplyView(QwikView):
    accept_form_class = AcceptForm
    template_name = 'player/reply.html'

    def get(self, request, *args, **kwargs):
        super().get(request)
        player = self.user.player
        appeal_pk = kwargs['appeal']
        appeal = Appeal.objects.get(pk=appeal_pk)
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
        context = {
            'appeal': appeal,
            'appeals': appeals,
            'bids': Bid.objects.filter(rival=player).all(),
            'next': next_pk,
            'player_id': player.facet(),
            'prev': prev_pk,
            'replies': replies,
        }
        context |= self.accept_form_class.get()
        context |= super().context(request)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        player = self.user.player
        context = self.accept_form_class.post(
            request.POST,
            player,
        )
        if len(context) == 0:
            return HttpResponseRedirect("/game/match/")
        return HttpResponseRedirect("/player/feed/replys/{}/".format(kwargs['appeal']))


class BidView(QwikView):
    bid_form_class = BidForm
    template_name = 'player/bid.html'

    def get(self, request, *args, **kwargs):
        super().get(request)
        player = self.user.player
        appeal_pk = kwargs['appeal']
        appeal = Appeal.objects.get(pk=appeal_pk)
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
        context = {
            'appeals': Appeal.objects.filter().all(),
            'appeal': appeal,
            'appeals': appeals,
            'next': next_pk,
            'player_id': player.facet(),
            'prev': prev_pk,
        }
        context |= self.bid_form_class.get(appeal)
        context |= super().context(request)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        player = self.user.player
        appeal_pk = kwargs['appeal']
        appeal = Appeal.objects.get(pk=appeal_pk)
        context = self.bid_form_class.post(
            request.POST,
            appeal,
        )
        if 'bid_form' in context:
            bids = Bid.objects.filter(rival=player).all()
            prev_pk = bids.last().pk
            next_pk = bids.first().pk
            found = False
            for a in bids:
                if found:
                    next_pk = a.pk
                    break
                if a.pk == bid.pk:
                    found = True
                else:
                    prev_pk = a.pk
            context |= {
                'appeals': Appeal.objects.filter().all(),
                'bid': bid,
                'bids': bids,
                'next': next_pk,
                'prev': prev_pk,
            }
            context |= super().context(request)
            return render(request, self.template_name, context)
        if 'accept' in context:
            bid = Bid(
                appeal=context['accept'],
                hours=context['hours'],
                rival=player,
                strength='m',
                )
            bid.save()
            bid.log_event('bid')
        return HttpResponseRedirect("/player/feed")


class RivalView(QwikView):

    def get(self, request, *args, **kwargs):
        super().request_init(request)
        context = super().context(request)
        return render(request, "player/rival.html", context)


class FiltersView(QwikView):
    screen_form_class = FiltersForm
    template_name = 'player/screen.html'

    def get(self, request, *args, **kwargs):
        super().get(request)
        player = self.user.player
        context = super().context(request)
        context |= {
            'appeals': Appeal.objects.all(),
            'bids': Bid.objects.filter(rival=player).all(),
        }
        context |= self.screen_form_class.get(self.user.player)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        context = self.screen_form_class.post(
            request.POST,
            self.user.player,
        )
        return HttpResponseRedirect("/player/feed/filters")