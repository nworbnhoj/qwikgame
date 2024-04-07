from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from qwikgame.views import QwikView


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