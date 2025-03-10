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
from qwikgame.hourbits import DAY_NONE, Hours24x7
from qwikgame.forms import MenuForm
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
            next_up = appeal.status + 'O'
            context |= {
                'next_up': NEXT_UP[next_up],
                'bids': self._bids(appeal, player),
                'log': appeal.log_filter(person.blocked()),
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
            next_up = appeal.status + ('B' if bid else '')
            context |= {
                'appeal': appeal,
                'log': appeal.log_filter(person.blocked()),
                'next_up': NEXT_UP[next_up],
                'rival': appeal.player,
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
        context |= self.bid_form_class.get(appeal)
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
            appeal.bid(player, bid)
            bid.announce(player)
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

    def _invite(self, appeal, invitees, request):
        appeal.invitees.clear()
        for friend in invitees:
            appeal.invitees.add(friend)
            invitee = friend.rival
            if invitee.user is None or invitee.user.person.notify_email:
                current_site = get_current_site(request)
                email_context={
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
                form = InviteForm(friend.email, email_context)
                if form.is_valid():
                    form.save(request)
                else:
                    logger.exception(f"Invalid InviteForm: {form}")
            else:
                logger.info(f'avoided invitation email to: {invitee}')
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
            return render(request, self.keen_template, context)
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
        today_hours = context.get('today', DAY_NONE)
        valid_hours = venue.open_date(today) & today_hours
        if valid_hours.is_none():
            appeal.delete()
            logger.info(f"no valid hours for {today_hours} {today} at {venue}")
        else:
            if appeal.hours24 != today_hours:
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
        tomorrow_hours = context.get('tomorrow', DAY_NONE)
        valid_hours = venue.open_date(tomorrow) & tomorrow_hours
        if valid_hours.is_none():
            appeal.delete()
            logger.info(f"no valid hours for {tomorrow_hours} {tomorrow} at {venue}")
        else:
            if appeal.hours24 != tomorrow_hours:
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
