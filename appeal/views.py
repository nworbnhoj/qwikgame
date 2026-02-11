import logging
from appeal.forms import AcceptForm, BidForm, KeenForm
from appeal.models import Appeal, Bid
from authenticate.views import RegisterView
from datetime import datetime, timedelta, timezone
from django.contrib.sites.shortcuts import get_current_site
from django.db.models import Q
from django.forms import BooleanField, CheckboxInput, CheckboxSelectMultiple, ChoiceField, Form, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, redirect, render
from game.models import Game, Match
from player.forms import FilterForm, FriendForm, FiltersForm, StrengthForm
from player.models import Filter, Friend, Player, Strength
from qwikgame.hourbits import DAY_NONE, Hours24x7
from qwikgame.forms import MenuForm
from qwikgame.log import Entry
from qwikgame.settings import FQDN
from api.models import Mark
from service.locate import Locate
from venue.models import Place, Region, Venue
from qwikgame.views import QwikView
from qwikgame.widgets import DAY_ALL, DAY_NONE, WEEK_ALL, WEEK_NONE


logger = logging.getLogger(__file__)

# NEXT_UP key is Appeal.status + Player_role 
NEXT_UP = {
    'A': '',
    'AB': 'Awaiting Confirmation',
    'AO': 'Waiting for replies from Players',
    'C': 'This Invitation has been cancelled',
    'CB': 'This Invitation has been cancelled',
    'CO': 'This Invitation has been cancelled',
    'D': 'A Match has been confirmed for this Invitation',
    'DB': 'Awaiting Confirmation',
    'DO': 'A Match has been confirmed for this Invitation',
    'X': 'This Invitation has expired',
    'XB': 'This Invitation has expired',
    'XO': 'This Invitation has expired',
}


class AppealsView(QwikView):
    appeals_template = 'appeal/appeals.html'

    def context(self, request, *args, **kwargs):
        player = self.user.player
        player.alert_del(type='abklm')
        appeals = player.appeals()
        kwargs['items'] = appeals
        if kwargs['items'].first():
            kwargs['pk'] = kwargs.get('appeal')
        context = super().context(request, *args, **kwargs)
        participate = player.appeal_participate()
        participate_list = list(participate)
        participate_list.sort(key=lambda x: x.last_hour.hour)
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
        if not kwargs.get('item'):
            return render(request, self.appeals_template, context)


class AcceptView(AppealsView):
    accept_form_class = AcceptForm
    accept_template = 'appeal/accept.html'

    def _bids(self, appeal, player):
        bids = Bid.objects.filter(appeal=appeal).exclude(hours=None)
        friends = Friend.objects.filter(player=player)
        for bid in bids:
            bid.hour_str = bid.hours24().as_str()
            bid.name = player.name_rival(bid.rival)
            bid.conduct_stars = bid.rival.conduct_stars 
        return {str(bid.pk): bid for bid in bids}

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        player = self.user.player
        person = self.user.person
        appeal = context.get('appeal')
        if appeal:
            log = appeal.log_filter(person.blocked())
            for entry in log:
                if 'id' in entry and entry['id'] != player.pk:
                    Entry(entry).rename(player)
            next_up = appeal.status + 'O'
            context |= {
                'notify_off': person.alert_str(False, 'bid'),
                'notify_on': person.alert_str(True, 'bid'),
                'next_up': NEXT_UP[next_up],
                'bids': self._bids(appeal, player),
                'log': log,
                'target': 'bid',
            }
        self._context = context
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        appeal = context.get('appeal')
        if not appeal:
            return HttpResponseRedirect(f'/appeal/')
        # mark this Appeal seen by this Player
        appeal.mark_seen([self.user.player.pk]).save()
        context |= self.accept_form_class.get()
        return render(request, self.accept_template, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        player = self.user.player
        appeal = Appeal.objects.filter(pk=kwargs['appeal']).first()
        if not appeal:
            return HttpResponseRedirect(f'/appeal/')
        context = self.accept_form_class.post(
            request.POST,
            player,
        )
        form = context.get('accept_form')
        if form and not form.is_valid():
            context |= self.context(request, *args, **kwargs)
            return render(request, self.accept_template, context)
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
                bid.decline(player)
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
    bid_template = 'appeal/bid.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        appeal = context.get('appeal')
        if appeal:
            bid = Bid.objects.filter(
                appeal = appeal,
                rival = self.user.player,
            ).first()
            player = self.user.player
            person = self.user.person
            log = appeal.log_filter(person.blocked())
            for entry in log:
                if 'id' in entry and entry['id'] != player.pk:
                    Entry(entry).rename(player)
            next_up = appeal.status + ('B' if bid else '')
            context |= {
                'appeal': appeal,
                'log': log,
                'next_up': NEXT_UP[next_up],
                'notify_off': person.alert_str(False, 'bid'),
                'notify_on': person.alert_str(True, 'bid'),
                'rival': appeal.player,
                'rival_name': player.name_rival(appeal.player),
                'strength': player.strength_str(appeal.game, appeal.player),
                'bid': bid,
            }
        self._context = context
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        appeal = context.get('appeal')
        if not appeal:
            return HttpResponseRedirect(f'/appeal/')
        # redirect if this Player owns the Appeal
        if appeal.player == self.user.player:
            return HttpResponseRedirect(f'/appeal/accept/{appeal.id}/')
        appeal.mark_seen([self.user.player.pk]).save()
        context['bid_form'] = self.bid_form_class(appeal=appeal)
        return render(request, self.bid_template, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        player = self.user.player
        appeal = context.get('appeal')
        if not appeal:
            return HttpResponseRedirect(f'/appeal/')
        context |= self.bid_form_class.post(
            request.POST,
            appeal,
        )
        form = context.get('bid_form')
        if form and not form.is_valid():
            return render(request, self.bid_template, context)
        if 'CANCEL' in context:
            try:
                cancel_pk = context.get('CANCEL')
                bid = Bid.objects.get(pk=cancel_pk)
                bid.cancel(player)
                bid.delete()
            except:
                logger.exception('failed to cancel bid: {} : {}'.format(player, cancel_pk))
            return HttpResponseRedirect(f'/appeal/{appeal.pk}/')
        if appeal.status == 'A':
            strength, confidence = appeal.player.strength_est(appeal.game, player)
            bid = Bid.objects.create(
                appeal=context.get('accept'),
                hours=context.get('hour', DAY_NONE).as_bytes(),
                rival=player,
                strength=strength,
                str_conf=confidence,
            )
            bid.announce(player)
            # Fast-forward to Match if only a single invitee
            if appeal.invitees.all().count() == 1:
                match = appeal.accept(bid.pk)
                if match:
                    return HttpResponseRedirect(f'/game/match/{match.id}/')
            # update the Mark size
            mark = Mark.objects.filter(game=appeal.game, place=appeal.venue).first()
            if mark:
                mark.save()
        return HttpResponseRedirect(f'/appeal/{appeal.id}/')




class KeenView(AppealsView):
    keen_form_class = KeenForm
    keen_template = 'appeal/keen.html'

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
        return render(request, self.keen_template, context)

    # notify all Players with a active matching Filter
    def _broadcast(appeal, filter_qs):
        if filter_qs.len() > 0:
            logger.info(f'Broadcasting notifications to {filter_qs.len()} Players')
            datetime = appeal.venue.datetime
            context = {
                'appeal': appeal,
                'date': datetime.strftime("%Y-%m-%d %A"),
                'game': appeal.game(),
                'domain': FQDN,
                'time': datetime.strftime("%Hh"),
                'venue': appeal.venue(),
            }
            for f in filters:
                player = f.player
                context |= {
                    'name': player.name_rival(appeal.player),
                    'recipient': player,
                }
                player.alert(
                    type='c',
                    expires=appeal.hours24.last_hour(),
                    context=context,
                    url=f'/appeal/{appeal.pk}/'
                )

    def _createAppeal(day, hours, game, venue, player, invitees, request):
        appeal, created = Appeal.objects.get_or_create(
            date=day.date(),
            game=game,
            player=player,
            venue=venue,
        )
        valid_hours = venue.open_date(day) & hours
        if valid_hours.is_none:
            appeal.delete()
            logger.info(f"no valid hours for {hours} {day} at {venue}")
        else:
            if appeal.hours24 != hours:
                appeal.set_hours(valid_hours)
                appeal.perish()
            self._invite(appeal, invitees, request)
            if created:
                appeal.log_event('appeal')
                logger.info(f'created Appeal: {appeal}')
            else:
                appeal.log_event('reappeal')
                logger.info(f'updated Appeal: {appeal}')
            return appeal
        return None

    def _filter_qs(appeal):
        qs = Filter.objects.filter(active=True)
        qs = qs.filter(
            Q(game=appeal.game) |
            Q(game__isnull=True)
        )
        qs = qs.filter(
            Q(place=appeal.venue) | 
            Q(place__venue__isnull=True, place__locality=appeal.venue.locality) | 
            Q(place__venue__isnull=True, place__locality__isnull=True, place__admin1=appeal.venue.admin1) | 
            Q(place__venue__isnull=True, place__locality__isnull=True, place__admin1__isnull=True, place__country=appeal.venue.country) | 
            Q(place__isnull=True)
        )
        # TODO filter by Filter.hours
        qs = qs.order_by('player').distinct('player')
        qs = qs.exclude(player__in=appeal.invitee_players)
        return qs

    def _getGame(gameid):
        game = Game.objects.filter(pk=gameid).first()
        if not game:
            logger.warn(f'Game missing from Appeal: {game}')
        return game   

    def _getVenue(placeid):
        place, venue = None, None
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
        return (place, venue)

    def _invite(self, appeal, invitees, request):
        appeal.invitees.clear()
        for friend in invitees:
            appeal.invitees.add(friend)
            current_site = get_current_site(request)
            context={
                'appeal': appeal,
                'date': appeal.venue.datetime(appeal.date).strftime("%b %d"),
                'domain': current_site.domain,
                'game': appeal.game,
                'name': appeal.player.qwikname,
                'protocol': 'https' if request.is_secure() else 'http',
                'site_name': current_site.name,
                'time': appeal.hours24.as_str(),
                'venue': appeal.venue,
            }
            url = f'/appeal/{appeal.pk}/',
            friend.alert('b', appeal.last_hour,  context, url, request)
        appeal.save()

    def _updateMarkSize(game, place):
        mark = Mark.objects.filter(game=game, place=place).first()
        if mark:
            mark.save()

    def _updateVenueGames(game, venue, place):
        if not(game in venue.games.all()):
            venue.games.add(game)
            logger.info(f'Venue Game add: {game}')
            venue.save()
            # TODO consider delay adding Mark until Match completed as Venue/Game combination
            # TODO Venue Manager to set and restrict Games at Venue
            mark = Mark(game=game, place=place, num_player=1)
            mark.save()
            logger.info(f'Mark new {mark}')

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
            return render(request, self.keen_template, context)
        place, venue = self._getVenue(context.get('placeid'))
        game = self._getGame(context.get('game'))
        if not (game and venue):
            return HttpResponseRedirect('/appeal/')
        self._updateVenueGames(game, venue, place)
        venue_now = venue.now()
        invitees = context.get('friends', [])
        today_appeal = self._createAppeal(
            venue_now,
            context.get('today', DAY_NONE),
            game,
            venue,
            player,
            invitees,
            request,
        )
        tomorrow_appeal = self._createAppeal(
            venue_now + timedelta(days=1),
            context.get('tomorrow', DAY_NONE),
            game,
            venue,
            player,
            invitees,
            request,
        )
        if today_appeal or tomorrow_appeal:
            self._updateMarkSize(game, place)
            filter_qs = self._filter_qs
            self._broadcast(today_appeal_pk, filter_qs)
            self._broadcast(tomorrow_appeal_pk, filter_qs)
        if today_appeal:
            return HttpResponseRedirect(f'/appeal/{today_appeal.pk}/')
        elif tommorow_appeal:
            return HttpResponseRedirect(f'/appeal/{tommorow_appeal.pk}/')
        else:
            return HttpResponseRedirect(f'/appeal/')


class RivalView(AppealsView):
    menu_form_class = MenuForm
    stats_template = 'appeal/rival.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        rival = Player.objects.filter(pk=kwargs.get('rival')).first()
        if rival:
            player = self.user.player
            person = self.user.person
            stats = rival.stats()
            context |= {
                'back': '/'.join((request.path).split('/')[:-2]),
                'games': stats.get('games', {}),
                'periods': stats.get('periods', {}),
                'places': stats.get('places', {}),
                'rival': rival,
                'rival_name': player.name_rival(rival),
            }
            appeal = context.get('appeal')
            if appeal:
                context['strength'] = player.strength_str(appeal.game, rival)
        self._context = context
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        back = kwargs.get('back', '/')
        rival = context.get('rival')
        if not rival:
            return HttpResponseRedirect(back)
        return render(request, self.stats_template, context)


    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        context = self.menu_form_class.post(request.POST)
        menu_form = context.get('menu_form')
        if menu_form and menu_form.is_valid():
            if 'BLOCK' in context:
                try:
                    rival_pk = context.get('BLOCK')
                    rival = Player.objects.get(pk=rival_pk)
                    self.user.person.block_rival(rival.user.person)
                    logger.info(f'Blocked: {self.user.person} blocked {rival.user.person}')
                except:
                    logger.exception("Block failed: {player} blocked {rival_pk}")
        back = '/'.join((request.path).split('/')[:-2])
        logger.warn(back)
        return HttpResponseRedirect(back)
