from django.http import HttpResponse
from django.shortcuts import get_object_or_404, render
from django.views import View


class AccountView(View):

    def get(self, request):
        return render(request, "player/account_player.html")


class AvailableView(View):

    def get(self, request):
        return render(request, "player/available.html")


class InviteView(View):

    def get(self, request):
        return render(request, "player/invite.html")


class RivalView(View):

    def get(self, request):
        return render(request, "player/rival.html")