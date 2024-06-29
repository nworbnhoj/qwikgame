import logging
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from django.views import View
from game.forms import ActiveForm, AvailableForm, ChatForm
from game.models import Game, Match
from player.models import Available, Player
from qwikgame.views import QwikView
from venue.models import Venue
from qwikgame.views import QwikView

logger = logging.getLogger(__file__)

class GameView(QwikView):

    def get(self, request, *args, **kwargs):
        super().request_init(request)
        player = self.user.player
        available = Available.objects.filter(player=player)
        context = {
            'games': player.games.all(),
            'available': available.all()
        }
        context |= super().context(request)
        if context['small_screen']:
            return render(request, "game/game.html", context)
        else:
            return HttpResponseRedirect("/game/active/")


class ActiveView(QwikView):
    active_form_class = ActiveForm
    template_name = 'game/active.html'

    def get(self, request, *args, **kwargs):
        super().get(request)
        player = self.user.player
        available = Available.objects.filter(
            player = player,
            game__in = player.games.all()
        )
        context = {'available': available.all()}
        context |= self.active_form_class.get(player)
        context |= super().context(request)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        self.active_form_class.post(request.POST, self.user.player)
        player = self.user.player        
        available = Available.objects.filter(player=player)
        for game in player.games.all():
            if len(available.filter(game=game)) == 0:
                url = "/game/{}".format(game)
                return HttpResponseRedirect(url)
        return HttpResponseRedirect("/game/")


class AvailableView(QwikView):
    available_form_class = AvailableForm
    hide=[]
    template_name = 'game/available.html'

    def get(self, request, *args, **kwargs):
        super().get(request)
        player, game, venue, hours = self.user.player, None, None, None
        available = Available.objects.filter(player=player)
        context = {'available': available.all()}
        game_name = kwargs.pop('game', None)
        venue_name = kwargs.pop('venue', None)
        if game_name is not None:
            game = get_object_or_404(Game, name=game_name)
            context |= { 'game': game, 'game_name': game.name }
        if venue_name is not None:
            venue = get_object_or_404(Venue, name=venue_name)
            context |= { 'venue': venue, 'venue_name': venue.name }
        context |= {'hide_view': 'hidden'}
        if (player is not None and game is not None and venue is not None):
            try:
                available = Available.objects.get(player=player, game=game, venue=venue)
                hours = bytes(available.hours)
                context['blocks'] = AvailableView.hour_blocks(available.get_hours_week(), range(6,21))
                context['hide_edit'] = 'hidden'
                context['hide_view'] = ''
            except:
                logger.exception("failed to get Available object")            
        context |= self.available_form_class.get(
            player,
            game=game.pk if game is not None else None,
            hide=self.hide,
            venue=venue.pk if venue is not None else None,
            hours=hours,
        )
        context |= super().context(request)
        return render(request, self.template_name, context)

    def hour_blocks(week, range):
        blocks=[]
        start, end, r_start, r_end = None, None, range[0], range[-1]
        for d, day in enumerate(week):
            grid_row = "{}/{}".format(d+1, d+1)
            for h, hour in enumerate(day):
                if h in range:
                    if hour and start is None:
                        start = h
                    elif start and not hour:
                        end = h - 1
                        grid_column = "{}/{}".format(start-r_start+2, end-r_start+3)
                        label = start if start == end else "{}-{}".format(start, end)
                        blocks.append((grid_column, grid_row, label))
                        start = None
            if start is not None:
                end = r_end
                grid_column = "{}/{}".format(start-r_start+2, end-r_start+3)
                label = start if start == end else "{}-{}".format(start, end)
                if start == r_start and end == r_end:
                    label = 'All day'
                blocks.append((grid_column, grid_row, label))
                start = None
        return blocks


    def post(self, request, *args, **kwargs):
        super().post(request)
        context = self.available_form_class.post(
            request.POST,
            self.user.player,
            hide=self.hide,
        )
        if len(context) == 0:
            return HttpResponseRedirect("/game/")
        context |= super().context(request)

        game, venue = None, None
        game_name = kwargs.pop('game', None)
        venue_name = kwargs.pop('venue', None)
        if game_name is not None:
            game = get_object_or_404(Game, name=game_name)
        if venue_name is not None:
            venue = get_object_or_404(Venue, name=venue_name)
        context |= {'game': game, 'game_name': game.name }

        return render(request, self.template_name, context)


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
        