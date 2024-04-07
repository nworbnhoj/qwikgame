from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from django.views import View
from game.forms import ActiveForm, AvailableForm
from player.models import Player
from qwikgame.views import QwikView


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
            return HttpResponseRedirect("/game/active/")
        context = context | super().context(request)
        return render(request, self.template_name, context)


class AvailableView(QwikView):
    pass
