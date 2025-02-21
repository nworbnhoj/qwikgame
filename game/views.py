import logging
from datetime import datetime, timedelta, timezone
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from django.views import View
from game.forms import MatchForm, ReviewForm
from game.models import Game, Match, Review
from player.models import Player, Strength
from qwikgame.constants import DELAY_MATCH_BANNER, DELAY_MATCH_CHAT, DELAY_MATCHS_LIST
from qwikgame.forms import MenuForm
from qwikgame.views import QwikView
from venue.models import Venue
from qwikgame.log import Entry
from qwikgame.views import QwikView


logger = logging.getLogger(__file__)


class MatchesView(QwikView):
    template_name = 'game/matches.html'

    def context(self, request, *args, **kwargs):
        kwargs['items'] = Match.objects.filter(competitors__in=[self.user.player]).order_by('date').reverse()
        if kwargs['items'].first():
            kwargs['pk'] = kwargs.get('match', kwargs['items'].first().pk)
        context = super().context(request, *args, **kwargs)
        player = self.user.player
        player.alert_del(type='match')
        matches = context['items']
        now = datetime.now(timezone.utc) + DELAY_MATCHS_LIST
        matches_future = matches.filter(date__gt=now).order_by('date')
        for match in matches_future:
            seen = player.pk in match.meta.get('seen', [])
            match.seen = '' if seen else 'unseen'
        matches_past = matches.filter(date__lte=now).order_by('date')
        for match in matches_past:
            seen = player.pk in match.meta.get('seen', [])
            match.seen = '' if seen else 'unseen'
        context['matches'] = matches
        context |= {
            'cta_disabled': '' if matches_future else 'disabled',
            'match': context['item'],
            'matches_future': matches_future,
            'matches_past': matches_past,
            'match_tab': 'selected',
            'player': player,
            'target': 'match',
        }
        return context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        if context['small_screen']:
            return render(request, self.template_name, context)
        if not kwargs.get('match'):
            first_match = context['matches'].first()
            if first_match:
                return HttpResponseRedirect(f'{request.path}{first_match.pk}/')
            context['match_tab'] = 'selected'
            return render(request, self.template_name, context)


class MatchView(MatchesView):
    match_form_class = MatchForm
    template_name = 'game/match.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        player = self.user.player
        match = context.get('match')
        if match:
            match_log_start = -1
            for i, entry in enumerate(match.log):
                if 'klass' in entry and 'scheduled' in entry['klass']:
                    match_log_start = i+1
                    break
            now = datetime.now(timezone.utc)
            if match.status == 'A':
                if now > match.date + DELAY_MATCH_BANNER:
                    match.status = 'C'
            match match.status:
                case 'A':
                    banner_txt = 'Match is scheduled!'
                    banner_class = 'active'
                case 'C':
                    banner_txt = 'Match is complete.'
                    banner_class = 'complete'
                case 'X':
                    banner_txt = 'Match is cancelled!'
                    banner_class = 'xancelled'
                case _:
                    banner_txt = 'unknown status'
                    banner_class = ''
            review = Review.objects.filter(match=match, player=player).first()
            icons = match.icons()
            icons.pop(player.pk, None)
            context |= {
                'banner_class': banner_class,
                'banner_txt': banner_txt,
                'enable_chat': now < match.date + DELAY_MATCH_CHAT,
                'rival_icons': icons.values(),
                'match_log_start': match_log_start,
                'review': review,
            }
        return context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        match = context['match']
        # mark this Match seen by this Player
        match.mark_seen([self.user.player.pk]).save()
        context |= self.match_form_class.get()
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        match_pk = kwargs['match']
        context = self.match_form_class.post(request.POST)
        form = context.get('chat_form')
        if form and not form.is_valid():
            return render(request, self.template_name, context)
        player = self.user.player
        match = Match.objects.get(pk=match_pk)
        if 'CANCEL' in context:
            try:
                cancel_pk = context.get('CANCEL')
                match = Match.objects.get(pk=cancel_pk)
                match.alert(player)
                logger.info(f'Cancelling Match: {match}')
                match.status = 'X'
                # mark this Match seen by this Player only
                match.meta['seen'] = [player.pk]
                match.save()
            except:
                logger.exception('failed to cancel match: {} : {}'.format(player, cancel_pk))
            return HttpResponseRedirect(f'/game/match/{cancel_pk}/')
        txt = context.get('txt')
        if txt:
            try:
                person = player.user.person
                match = Match.objects.get(pk=match_pk)
                match.alert(player)
                entry = Entry(
                    icon = person.icon,
                    id = player.pk,
                    klass = 'chat',
                    name = person.qwikname,
                    text = txt,
                )
                match.meta['seen'] = [player.pk]
                match.log_entry(entry)
            except:
                logger.exception(f'failed chat entry: {match_pk} {context}')
        return HttpResponseRedirect(f'/game/match/{match_pk}/')


class ReviewsView(QwikView):
    template_name = 'game/reviews.html'

    def _reviews(self):
        return Review.objects.filter(player=self.user.player)

    def context(self, request, *args, **kwargs):
        kwargs['items'] = Review.objects.filter(player=self.user.player)
        if kwargs['items'].first():
            kwargs['pk'] = kwargs.get('review', kwargs['items'].first().pk)
        context = super().context(request, *args, **kwargs)
        player = self.user.player
        player.alert_del(type='review')
        reviews = context['items'].order_by('match__date')
        for review in reviews:
            seen = player.pk in review.meta.get('seen', [])
            review.seen = '' if seen else 'unseen'
        context |= {
            'cta_disabled': '' if reviews else 'disabled',
            'review': context['item'],
            'reviews': reviews,
            'review_tab': 'selected',
            'target': 'review',
        }
        return context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        if context['small_screen']:
            return render(request, self.template_name, context)
        if not kwargs.get('review'):
            first_review = context['reviews'].first()
            if first_review:
                return HttpResponseRedirect(f'{request.path}{first_review.pk}/')
            return render(request, self.template_name, context)


class ReviewView(ReviewsView):
    review_form_class = ReviewForm
    template_name = 'game/review.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        # TODO refine matches to unreviewed Matches
        context['target']='review'
        return context

    def _rivals(self, review):
        if review:
            player = self.user.player
            competitors = review.match.competitors.all()
            return {p.pk:'name' for p in competitors if p != player}
        return {}

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        review = context['review']
        # mark this Review seen by this Player
        review.mark_seen([self.user.player.pk]).save()
        rivals = self._rivals(context.get('review'))
        request.session['rivals'] = rivals
        context |= self.review_form_class.get(rivals)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        rivals = request.session.get('rivals')
        context = self.review_form_class.post(request.POST, rivals)
        form = context.get('review_form')
        if form and not form.is_valid():
            context |= self.context(request, *args, **kwargs)
            return render(request, self.template_name, context)
        try:
            review = Review.objects.get(pk=kwargs['review'])
            player = self.user.player
            rival = Player.objects.get(pk=context['rival'])
            rival.conduct_add(context['conduct_good'])
            rival.save()
            Strength.objects.update_or_create(
                game = review.match.game,
                player = player,
                rival = rival,
                defaults = {
                    'date': review.match.date,
                    'relative': context['strength'],
                    'weight': 3
                }
            )
            review.log_event('review')
            review.delete()
        except:
            logger.exception(f'failed opinion: {review} {context}')
        return HttpResponseRedirect(f'/game/match/review/')


class RivalView(MatchesView):
    menu_form_class = MenuForm
    template_name = 'player/stats.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        match = context.get('match')
        player = self.user.player
        rivals = match.rivals(player)
        rival = Player.objects.filter(pk=rivals[0]).first()
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
                'strength': player.strength_str(match.game, rival),
            }
        self._context = context
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        back = kwargs.get('back', '/')
        rival = context.get('rival')
        if not rival:
            return HttpResponseRedirect(back)
        return render(request, self.template_name, context)


    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        context = self.menu_form_class.post(request.POST)
        menu_form = context.get('menu_form')
        if menu_form and menu_form.is_valid():
            if 'BLOCK' in context:
                try:
                    rival_pk = context['BLOCK']
                    rival = Player.objects.get(pk=rival_pk)
                    self.user.person.block_rival(rival.user.person)
                    logger.info(f'Blocked: {self.user.person} blocked {rival.user.person}')
                except:
                    logger.exception("Block failed: {player} blocked {rival_pk}")
        back = '/'.join((request.path).split('/')[:-2])
        return HttpResponseRedirect(back)