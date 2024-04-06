from django.contrib.auth.decorators import login_required
from django.utils.decorators import method_decorator
from django.views.generic import TemplateView

from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from django.views import View

from person.models import LANGUAGE, Person, Social
from person.forms import PublicForm as PersonPublicForm
from player.models import Game, Player, Precis
from player.forms import PublicForm, PrecisForm
from qwikgame.views import QwikView


class AvailableView(QwikView):

    def get(self, request, *args, **kwargs):
        super().request_init(request)
        context = super().context(request)
        if context['small_screen']:
            return render(request, "player/available.html", context)
        else:
            return HttpResponseRedirect("/player/game/add/")







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