from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from django.views import View
from game.forms import ActiveForm, AvailableForm
from game.models import Game
from player.models import Available, Player
from qwikgame.views import QwikView
from venue.models import Venue


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
        available = Available.objects.filter(player=player)
        context = {'available': available.all()}
        context |= self.active_form_class.get(player)
        context |= super().context(request)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        context = self.active_form_class.post(request.POST, self.user.player)
        if len(context) == 0:
            return HttpResponseRedirect("/game/")
        context |= super().context(request)
        return render(request, self.template_name, context)


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
                hours: bytearray = available.hours
                context['blocks'] = AvailableView.hour_blocks(available.get_hours_week(), range(6,21))
                context['hide_edit'] = 'hidden'
                context['hide_view'] = ''
            except:
                pass             
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


class MatchView(View):

    def get(self, request):
        return render(request, "game/match.html")