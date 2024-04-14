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
        context = super().context(request)
        if context['small_screen']:
            return render(request, "game/game.html", context)
        else:
            return HttpResponseRedirect("/game/active/")


class ActiveView(QwikView):
    active_form_class = ActiveForm
    template_name = 'game/active.html'

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = self.active_form_class.get(self.user.player)
        context = context | super().context(request)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        context = self.active_form_class.post(request.POST, self.user.player)
        if len(context) == 0:
            return HttpResponseRedirect("/game/")
        context = context | super().context(request)
        return render(request, self.template_name, context)


class AvailableView(QwikView):
    available_form_class = AvailableForm
    hide=[]
    template_name = 'game/available.html'

    def get(self, request, *args, **kwargs):
        super().get(request)
        player, game, venue, hours = self.user.player, None, None, None
        game_name = kwargs.pop('game', None)
        venue_name = kwargs.pop('venue', None)
        if game_name is not None:
            game = get_object_or_404(Game, name=game_name)
            context = { 'game': game, 'game_name': game.name }
        if venue_name is not None:
            venue = get_object_or_404(Venue, name=venue_name)
            context = context | { 'venue': venue, 'venue_name': venue.name }
        if (player is not None and game is not None and venue is not None):
            try:
                hours: bytearray = Available.objects.get(player=player, game=game, venue=venue).hours
            except:
                pass                
        context = context | self.available_form_class.get(
            player,
            game=game.pk if game is not None else None,
            hide=self.hide,
            venue=venue.pk if venue is not None else None,
            hours=hours,
        )
        context = context | super().context(request)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        context = self.available_form_class.post(
            request.POST,
            self.user.player,
            hide=self.hide,
        )
        if len(context) == 0:
            return HttpResponseRedirect("/game/")
        context = context | super().context(request)

        game, venue = None, None
        game_name = kwargs.pop('game', None)
        venue_name = kwargs.pop('venue', None)
        if game_name is not None:
            game = get_object_or_404(Game, name=game_name)
        if venue_name is not None:
            venue = get_object_or_404(Venue, name=venue_name)
        context = context | {'game': game, 'game_name': game.name }

        return render(request, self.template_name, context)