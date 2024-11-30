import logging, pytz
from datetime import datetime, timedelta
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from django.views import View
from game.forms import MatchForm, ReviewForm
from game.models import Game, Match, Review
from player.models import Player, Strength
from qwikgame.constants import DELAY_MATCH_BANNER, DELAY_MATCH_CHAT, DELAY_MATCHS_LIST, MATCH_STATUS
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
        super().context(request, *args, **kwargs)
        player = self.user.player
        matches = self._context['items']
        now = datetime.now(pytz.utc) + DELAY_MATCHS_LIST
        matches_future = matches.filter(date__gt=now)
        for match in matches_future:
            seen = player.pk in match.meta.get('seen', [])
            match.seen = '' if seen else 'unseen'
        matches_past = matches.filter(date__lte=now)
        for match in matches_past:
            seen = player.pk in match.meta.get('seen', [])
            match.seen = '' if seen else 'unseen'
        self._context['matches'] = matches
        self._context |= {
            'match': self._context['item'],
            'matches_future': matches_future,
            'matches_past': matches_past,
            'player_id': self.user.player.facet(),
            'target': 'match',
        }
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        if context['small_screen']:
            return render(request, self.template_name, context)
        if not kwargs.get('match'):
            first_match = self._context['matches'].first()
            if first_match:
                return HttpResponseRedirect(f'{request.path}{first_match.pk}/')
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
            now = datetime.now(pytz.utc)
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
            self._context |= {
                'banner_class': banner_class,
                'banner_txt': banner_txt,
                'enable_chat': now < match.date + DELAY_MATCH_CHAT,
                'rival_icons': icons.values(),
                'match_log_start': match_log_start,
                'review': review,
                'schedule_tab': 'selected',
             }
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        match = self._context['match']
        # mark this Match seen by this Player
        match.mark_seen([self.user.player.pk]).save()
        context = self.context(request, *args, **kwargs)
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
        if 'CANCEL' in context:
            try:
                cancel_pk = context.get('CANCEL')
                match = Match.objects.get(pk=cancel_pk)
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
                entry = Entry(
                    icon = person.icon,
                    id = player.facet(),
                    klass = 'chat',
                    name = person.name,
                    text = txt,
                )
                match.log_entry(entry)
            except:
                logger.exception(f'failed chat entry: {match_pk} {context}')
        # mark this Match seen by this Player only
        match.meta['seen'] = [player.pk]
        match.save()
        context |= self.context(request, *args, **kwargs)
        return HttpResponseRedirect(f'/game/match/{match_pk}/')


class ReviewsView(QwikView):
    template_name = 'game/reviews.html'

    def _reviews(self):
        return Review.objects.filter(player=self.user.player)

    def context(self, request, *args, **kwargs):
        kwargs['items'] = Review.objects.filter(player=self.user.player)
        if kwargs['items'].first():
            kwargs['pk'] = kwargs.get('review', kwargs['items'].first().pk)
        super().context(request, *args, **kwargs)
        self._context |= {
            'review': self._context['item'],
            'reviews': self._context['items'].order_by('match__date'),
            'player_id': self.user.player.facet(),
            'review_tab': 'selected',
            'target': 'review',
        }
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        if context['small_screen']:
            return render(request, self.template_name, context)
        if not kwargs.get('review'):
            first_review = self._context['reviews'].first()
            if first_review:
                return HttpResponseRedirect(f'{request.path}{first_review.pk}/')
            return render(request, self.template_name, context)


class ReviewView(ReviewsView):
    review_form_class = ReviewForm
    template_name = 'game/review.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        # TODO refine matches to unreviewed Matches
        self._context |= {
            'target': 'review',
            }
        return self._context

    def _rivals(self, review):
        if review:
            player = self.user.player
            competitors = review.match.competitors.all()
            return {p.pk:'name' for p in competitors if p != player}
        return {}

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
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
                    date: review.match.date,
                    relative: context['strength'],
                    weight: 3
                }
            )
            review.log_event('review')
            review.delete()
        except:
            logger.exception(f'failed opinion: {review} {context}')
        return HttpResponseRedirect(f'/game/match/review/')
        