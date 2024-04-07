from django.contrib.auth.decorators import login_required
from django.utils.decorators import method_decorator
from django.views.generic import TemplateView

from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from django.views import View

from person.models import LANGUAGE, Person, Social
from person.forms import PublicForm as PersonPublicForm
from player.models import Game, Player, Precis
from player.forms import ActiveForm, PublicForm, PrecisForm
from qwikgame.views import QwikView


class GameView(QwikView):

    def get(self, request, *args, **kwargs):
        super().request_init(request)
        context = super().context(request)
        if context['small_screen']:
            return render(request, "player/game.html", context)
        else:
            return HttpResponseRedirect("/player/game/active/")


class ActiveView(QwikView):
    active_form_class = ActiveForm
    template_name = 'player/active.html'

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = self.active_form_class.get(self.user.player)
        context = context | super().context(request)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        context = self.active_form_class.post(request.POST, self.user.player)
        if len(context) == 0:
            return HttpResponseRedirect("/player/game/active/")
        context = context | super().context(request)
        return render(request, self.template_name, context)


class AvailableView(QwikView):
    pass
class InviteView(QwikView):

    def get(self, request, *args, **kwargs):
        super().request_init(request)
        context = super().context(request)
        return render(request, "player/invite.html", context)


class RivalView(QwikView):

    def get(self, request, *args, **kwargs):
        super().request_init(request)
        context = super().context(request)
        return render(request, "player/rival.html", context)