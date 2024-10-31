import logging, pytz
from datetime import datetime, timedelta
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from django.views import View
from game.forms import MatchForm, ReviewForm
from game.models import Game, Match, Review
from player.models import Player, Strength
from qwikgame.constants import STRENGTH
from qwikgame.views import QwikView
from venue.models import Venue
from qwikgame.log import Entry
from qwikgame.views import QwikView


logger = logging.getLogger(__file__)


class MatchesView(QwikView):
    template_name = 'game/matches.html'

    def _matches(self):
        return Match.objects.filter(competitors__in=[self.user.player])

    def context(self, request, *args, **kwargs):
        super().context(request, *args, **kwargs)
        player = self.user.player
        matches = self._matches().all().order_by('date')
        now = datetime.now(pytz.utc) + timedelta(minutes=10)
        self._context |= {
            'matches_future': matches.filter(date__gt=now),
            'matches_past': matches.filter(date__lte=now),
            'player_id': player.facet(),
            'target': 'match',
        }
        if matches.first():
            match_pk = kwargs.get('match', matches.first().pk)
            prev_pk = matches.last().pk
            next_pk = matches.first().pk
            found = False
            for m in matches:
                if found:
                    next_pk = m.pk
                    break
                if m.pk == match_pk:
                    found = True
                else:
                    prev_pk = m.pk
            self._context |= {
                'match': Match.objects.get(pk=match_pk),
                'next': next_pk,
                'prev': prev_pk,
            }
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        if context['small_screen']:
            return render(request, self.template_name, context)
        if not kwargs.get('match'):
            first_match = self._matches().all().order_by('date').first()
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
            self._context |= {
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
        super().context(request, *args, **kwargs)
        player = self.user.player
        reviews = self._reviews().all().order_by('match__date')
        self._context |= {
            'reviews': reviews,
            'player_id': player.facet(),
            'target': 'review',
        }
        if reviews.first():
            review_pk = kwargs.get('review', reviews.first().pk)
            prev_pk = reviews.last().pk
            next_pk = reviews.first().pk
            found = False
            for r in reviews:
                if found:
                    next_pk = r.pk
                    break
                if r.pk == review_pk:
                    found = True
                else:
                    prev_pk = r.pk
            self._context |= {
                'review': Review.objects.get(pk=review_pk),
                'next': next_pk,
                'prev': prev_pk,
            }
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        if context['small_screen']:
            return render(request, self.template_name, context)
        if not kwargs.get('review'):
            first_review = self._reviews().all().order_by('match__date').first()
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
        