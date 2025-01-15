import logging
from appeal.forms import AcceptForm, BidForm, InviteForm, KeenForm
from appeal.models import Appeal, Bid
from authenticate.views import RegisterView
from datetime import datetime, timedelta, timezone
from django.contrib.sites.shortcuts import get_current_site
from django.forms import BooleanField, CheckboxInput, CheckboxSelectMultiple, ChoiceField, Form, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, redirect, render
from game.models import Game, Match
from player.forms import FilterForm, FriendForm, FiltersForm, StrengthForm
from player.models import Filter, Friend, Player, Strength
from qwikgame.hourbits import Hours24x7
from api.models import Mark
from service.locate import Locate
from venue.models import Place, Region, Venue
from qwikgame.views import QwikView
from qwikgame.widgets import DAY_ALL, DAY_NONE, WEEK_ALL, WEEK_NONE


logger = logging.getLogger(__file__)


class AppealsView(QwikView):
    template_name = 'appeal/appeals.html'

    def context(self, request, *args, **kwargs):
        player = self.user.player
        player.alert_del(type='appeal')
        appeals = player.appeals()
        kwargs['items'] = appeals
        if kwargs['items'].first():
            kwargs['pk'] = kwargs.get('appeal')
        context = super().context(request, *args, **kwargs)
        participate = player.appeal_participate()
        participate_list = list(participate)
        participate_list.sort(key=lambda x: x.last_hour)
        participate_list.sort(key=lambda x: x.date)
        for appeal in participate_list:
            seen = player.pk in appeal.meta.get('seen', [])
            appeal.seen = '' if seen else 'unseen'
        appeals = appeals.exclude(pk__in=participate)
        appeals_list = list(appeals)  
        appeals_list.sort(key=lambda x: x.last_hour)
        appeals_list.sort(key=lambda x: x.date)
        for appeal in appeals_list:
            seen = player.pk in appeal.meta.get('seen', [])
            appeal.seen = '' if seen else 'unseen'
        context |= {  
            'appeal': context.get('item'),
            'appeals': appeals_list[:100],
            'appeals_tab': 'selected',
            'appeals_length': len(appeals_list),
            'filtered': Filter.objects.filter(player=player, active=True).exists(),
            'player': player,
            'prospects': participate_list[:100],
        }
        self._context = context
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        appeal = context.get('appeal')
        if not appeal: 
            return render(request, self.template_name, context)


class AcceptView(AppealsView):
    accept_form_class = AcceptForm
    template_name = 'appeal/accept.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        player = self.user.player
        appeal = context.get('appeal')
        if appeal:
            bids = Bid.objects.filter(appeal=appeal).exclude(hours=None)
            friends = Friend.objects.filter(player=player)
            for bid in bids:
                bid.hour_str = bid.hours24().as_str()
                bid.name = player.name_rival(bid.rival)
                bid.conduct_stars = bid.rival.conduct_stars 
            bids = {str(bid.pk): bid for bid in bids}
            context |= {
                'player_id': player.facet(),
                'bids': bids,
                'target': 'bid',
            }
        self._context = context
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        appeal = context.get('appeal')
        # mark this Appeal seen by this Player
        appeal.mark_seen([self.user.player.pk]).save()
        context |= self.accept_form_class.get()
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        player = self.user.player
        appeal = Appeal.objects.filter(pk=kwargs['appeal']).first()
        context = self.accept_form_class.post(
            request.POST,
            player,
        )
        form = context.get('accept_form')
        if form and not form.is_valid():
            context |= self.context(request, *args, **kwargs)
            return render(request, self.template_name, context)
        if 'CANCEL' in context:
            if context.get('CANCEL') == appeal.pk:    # sanity check
                appeal = appeal.cancel()
                if appeal:
                    return HttpResponseRedirect(f'/appeal/{appeal.pk}/')
            return HttpResponseRedirect(f'/appeal/')
        try:
            bid_pk = context.get('accept')
            if bid_pk:
                match = appeal.accept(bid_pk)
                if match:
                    return HttpResponseRedirect(f'/game/match/{match.id}/')
            return HttpResponseRedirect(f'/appeal/{appeal.pk}/')
            decline_pk = context.get('decline')
            if decline_pk:
                bid = Bid.objects.get(pk=decline_pk)
                bid.rival.alert('appeal')
                bid.log_event('decline')
                bid.delete()
            if appeal:
                # mark this Appeal seen by this Player only
                appeal.meta['seen'] = [player.pk]
                appeal.save()
                mark = Mark.objects.filter(game=appeal.game, place=appeal.venue).first()
                if mark:
                    mark.save()
            return HttpResponseRedirect(f'/appeal/{appeal.pk}/')
        except:
            logger.exception(f'failed to process Bid: {context}')
        return HttpResponseRedirect(f'/appeal/')


class BidView(AppealsView):
    bid_form_class = BidForm
    template_name = 'appeal/bid.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        appeal = context.get('appeal')
        bid = Bid.objects.filter(
            appeal = appeal,
            rival = self.user.player,
        ).first()
        player = self.user.player
        context |= {
            'rival': appeal.player,
            'strength': player.strength_str(appeal.game, appeal.player),
            'player_id': player.facet(),
            'bid': bid,
        }
        self._context = context
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        appeal = context.get('appeal')
        # redirect if this Player owns the Appeal
        if appeal.player == self.user.player:
            return HttpResponseRedirect(f'/appeal/accept/{appeal.id}/')
        appeal.mark_seen([self.user.player.pk]).save()
        context |= self.bid_form_class.get(appeal)
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
                bid = Bid.objects.get(pk=cancel_pk)
                appeal_pk = bid.appeal.pk
                appeal.player.alert('appeal')
                # mark this Appeal seen by this Player only
                appeal.meta['seen'] = [player.pk]
                bid.log_event('withdraw')
                bid.delete()
            except:
                logger.exception('failed to cancel bid: {} : {}'.format(player, cancel_pk))
            return HttpResponseRedirect(f'/appeal/{appeal.pk}/')
        if appeal.status == 'A':
            strength, confidence = appeal.player.strength_est(appeal.game, player)
            bid = Bid.objects.create(
                appeal=context['accept'],
                hours=context['hour'].as_bytes(),
                rival=player,
                strength=strength,
                str_conf=confidence,
            )
            bid.log_event('bid')
            appeal.player.alert('appeal')
            # mark this Appeal seen by this Player only
            appeal.meta['seen'] = [player.pk]
            appeal.save()
            # update the Mark size
            mark = Mark.objects.filter(game=appeal.game, place=appeal.venue).first()
            if mark:
                mark.save()
        return HttpResponseRedirect(f'/appeal/{appeal.id}/')




class KeenView(AppealsView):
    keen_form_class = KeenForm
    template_name = 'appeal/keen.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        is_admin = self.user.is_admin
        context |= {
            'onclick_place_marker': 'select' if is_admin else 'noop',
            'onclick_region_marker': 'center',
            'onclick_search_marker': 'select',
            'onclick_venue_marker': 'select',
            'onhover_place_marker': 'info',
            'onhover_region_marker': 'info',
            'onhover_search_marker': 'info',
            'onhover_venue_marker': 'info',
            'onpress_place_marker': 'info',
            'onpress_region_marker': 'info',
            'onpress_search_marker': 'info',
            'onpress_venue_marker': 'info',
            'show_search_box': 'SHOW' if is_admin else 'HIDE',
        }
        self._context = context
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        context |= self.keen_form_class.get(
            player = self.user.player,
            game = kwargs.get('game'),
        )
        return render(request, self.template_name, context)

    def _invite(self, appeal, invitees, request):
        appeal.invitees.clear()
        for friend in invitees:
            appeal.invitees.add(friend.rival)
            if friend.rival.user is None:
                current_site = get_current_site(request)
                email_context={
                    'appeal': appeal,
                    'date': appeal.venue.datetime(appeal.date).strftime("%b %d"),
                    'domain': current_site.domain,
                    'game': appeal.game,
                    'name': appeal.player.user.person.name,
                    'protocol': 'https' if request.is_secure() else 'http',
                    'site_name': current_site.name,
                    'time': appeal.hours24.as_str(),
                    'venue': appeal.venue,
                }
                form = InviteForm(friend.email, email_context)
                logger.info(form.is_bound)
                if form.is_valid():
                    form.save(request)
                else:
                    logger.exception(f"Invalid InviteForm: {form}")
        appeal.save()

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        player = self.user.player 
        context = self.keen_form_class.post(
            request.POST,
            player,
        )
        form = context.get('keen_form')
        if form and not form.is_valid():
            context |= self.context(request, *args, **kwargs)
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
            return HttpResponseRedirect('/appeal/')
        gameid = context.get('game')
        game = Game.objects.filter(pk=gameid).first()
        if not game:
            logger.warn(f'Game missing from Appeal: {game}')
            return HttpResponseRedirect('/appeal/')
        # add this Game to a Venue if required
        if not(game in venue.games.all()):
            venue.games.add(game)
            logger.info(f'Venue Game add: {game}')
            venue.save()
            # TODO consider delay adding Mark until Match completed as Venue/Game combination
            # TODO Venue Manager to set and restrict Games at Venue
            mark = Mark(game=game, place=place, num_player=1)
            mark.save()
            logger.info(f'Mark new {mark}')
        invitees = context.get('friends', [])
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
        else:
            if appeal.hours24 != context['today']:
                appeal.set_hours(valid_hours)
                appeal.perish()
            self._invite(appeal, invitees, request)
            if created:
                logger.info(f'created Appeal: {appeal}')
            else:
                logger.info(f'updated Appeal: {appeal}')
            appeal.log_event('appeal')
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
        else:
            if appeal.hours24 != context['tomorrow']:
                appeal.set_hours(valid_hours)
                appeal.perish()
            self._invite(appeal, invitees, request)
            if created:
                logger.info(f'created Appeal: {appeal}')
            else:
                logger.info(f'updated Appeal: {appeal}')
            appeal.log_event('appeal')
            if not appeal_pk:
                appeal_pk = appeal.pk
        # update the Mark size
        mark = Mark.objects.filter(game=game, place=place).first()
        if mark:
            mark.save()
        if appeal_pk:
            return HttpResponseRedirect(f'/appeal/{appeal_pk}/')
        else:
            return HttpResponseRedirect(f'/appeal/')


