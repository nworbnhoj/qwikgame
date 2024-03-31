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


class AvailableView(View):

    def get(self, request):
        return render(request, "player/available.html")


class InviteView(View):

    def get(self, request):
        return render(request, "player/invite.html")


class RivalView(View):

    def get(self, request):
        return render(request, "player/rival.html")