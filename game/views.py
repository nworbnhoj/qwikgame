import logging, pytz
from datetime import datetime, timedelta
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from django.views import View
from game.forms import MatchForm, ReviewForm
from game.models import Game, Match, Review
from player.models import Player, Strength
from qwikgame.constants import DELAY_MATCH_BANNER, DELAY_MATCH_CHAT, DELAY_MATCHS_LIST, STRENGTH
from qwikgame.views import QwikView
from venue.models import Venue
from qwikgame.log import Entry
from qwikgame.views import QwikView


logger = logging.getLogger(__file__)


class MatchesView(QwikView):
    template_name = 'game/matches.html'

    def context(self, request, *args, **kwargs):
        kwargs['items'] = Match.objects.filter(competitors__in=[self.user.player])
        if kwargs['items'].first():
            kwargs['pk'] = kwargs.get('match', kwargs['items'].first().pk)
        super().context(request, *args, **kwargs)
        self._context['matches'] = self._context['items'].order_by('date')
        now = datetime.now(pytz.utc) + DELAY_MATCHS_LIST
        self._context |= {
            'match': self._context['item'],
            'matches_future': self._context['items'].filter(date__gt=now),
            'matches_past': self._context['items'].filter(date__lte=now),
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
            match_log_start = len(match.log) + 1
            for i, entry in enumerate(match.log):
                if 'klass' in entry and 'scheduled' in entry['klass']:
                    match_log_start = i+1
                    break
            now = datetime.now(pytz.utc)
            self._context |= {
                'enable_banner': now < match.date + DELAY_MATCH_BANNER,
                'enable_chat': now < match.date + DELAY_MATCH_CHAT,
                'match_log_start': match_log_start,
                'schedule_tab': 'selected',
             }
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
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
        try:
            player = self.user.player
            match = Match.objects.get(pk=match_pk)
            entry = Entry(
                icon = player.user.person.icon,
                id = player.facet(),
                klass = 'chat',
                name = player.user.person.name,
                text = context.get('txt'),
            )
            match.log_entry(entry)
        except:
            logger.exception(f'failed chat entry: {match_pk} {context}')
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
            'review_tab': 'selected',
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
            rival.conduct_add(context['conduct'])
            rival.save()
            Strength.objects.create(
                date = review.match.date,
                game = review.match.game,
                player = player,
                rival = rival,
                relative=context['strength'],
                weight=3
            )
            review.log_event('review')
            review.delete()
        except:
            logger.exception(f'failed opinion: {review} {context}')
        return HttpResponseRedirect(f'/game/match/review/')
        