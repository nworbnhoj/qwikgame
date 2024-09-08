import logging
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from django.views import View
from game.forms import ChatForm
from game.models import Game, Match
from player.models import Player
from qwikgame.views import QwikView
from venue.models import Venue
from qwikgame.views import QwikView

logger = logging.getLogger(__file__)


class MatchView(QwikView):
    template_name = 'game/matches.html'

    def get(self, request, *args, **kwargs):
        super().request_init(request)
        player = self.user.player
        matches = Match.objects.filter(competitors__in=[player]).all().order_by('date')
        context = { 'matches': matches, }
        context |= super().context(request)
        if context['small_screen']:
            return render(request, self.template_name, context)
        elif 'match' in kwargs:            
            return HttpResponseRedirect("/game/match/{}/".format(kwargs['match']))
        elif len(matches) > 0:
            first_match_id = matches[0].pk
            return HttpResponseRedirect("/game/match/{}/".format(first_match_id))
        else:
            return render(request, self.template_name, context)


class ChatView(QwikView):
    chat_form_class = ChatForm
    template_name = 'game/match.html'

    def get(self, request, *args, **kwargs):
        super().request_init(request)
        player = self.user.player
        match_pk = kwargs['match']
        match = Match.objects.get(pk=match_pk)
        matches = Match.objects.filter(competitors__in=[player]).all().order_by('date')
        prev_pk = matches.last().pk
        next_pk = matches.first().pk
        found = False
        for m in matches:
            if found:
                next_pk = m.pk
                break
            if m.pk == match.pk:
                found = True
            else:
                prev_pk = m.pk
        match_log_start = len(match.log) + 1
        for i, entry in enumerate(match.log):
            if 'klass' in entry and 'scheduled' in entry['klass']:
                match_log_start = i+1
                break
        context = {
            'match': match,
            'match_log_start': match_log_start,
            'matches': matches,
            'next': next_pk,
            'player_id': player.facet(),
            'prev': prev_pk,
        }
        context |= self.chat_form_class.get()
        context |= super().context(request)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().request_init(request)
        match_pk = kwargs['match']
        self.chat_form_class.post(
            request.POST,
            Match.objects.get(pk=match_pk),
            self.user.player,
        )
        return HttpResponseRedirect("/game/match/{}/".format(match_pk))
        