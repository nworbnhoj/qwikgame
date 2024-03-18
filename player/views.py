from django.http import HttpResponse
from django.shortcuts import get_object_or_404, render
from django.views import View

from person.models import Social
from player.models import Player, Precis
    

class AccountView(View):

    def get(self, request):
        user_id = request.user.id
        player = Player.objects.get(user__id=user_id)
        context = {
            "email": request.user.email,
            "name": request.user.person.name,
            "precis": Precis.objects.filter(player__user__id=user_id),
            "reputation": player.reputation(),
            "social": Social.objects.filter(person__user__id=user_id),
        }
        return render(request, "player/account_player.html", context)


class AvailableView(View):

    def get(self, request):
        return render(request, "player/available.html")


class InviteView(View):

    def get(self, request):
        return render(request, "player/invite.html")


class RivalView(View):

    def get(self, request):
        return render(request, "player/rival.html")