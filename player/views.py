from django.http import HttpResponse
from django.shortcuts import get_object_or_404, render
from django.views import View

from person.models import LANGUAGE, Person, Social
from player.models import Player, Precis


class AvailableView(View):

    def get(self, request):
        return render(request, "player/available.html")


class InviteView(View):

    def get(self, request):
        return render(request, "player/invite.html")




class PrivateView(View):

    def get(self, request):
        user_id = request.user.id
        person = Person.objects.get(user__id=user_id)
        player = Player.objects.get(user__id=user_id)
        languages = dict(LANGUAGE)
        context = {
            "blocked": player.blocked.all(),
            "email": request.user.email,
            "language": person.language,
            "languages": languages,
            "location_auto": "checked" if person.location_auto else "",
            "notify_email": "checked" if person.notify_email else "",
            "notify_web": "checked" if person.notify_web else "",
        }
        return render(request, "player/private.html", context)


class PublicView(View):

    def get(self, request):
        user_id = request.user.id
        person = Person.objects.get(user__id=user_id)
        player = Player.objects.get(user__id=user_id)
        languages = dict(LANGUAGE)
        context = {
            "name": request.user.person.name,
            "precis": Precis.objects.filter(player__user__id=user_id),
            "reputation": player.reputation(),
            "social": Social.objects.filter(person__user__id=user_id),
        }
        return render(request, "player/public.html", context)


class UpgradeView(View):

    def get(self, request):
        return render(request, "player/upgrade.html")



class RivalView(View):

    def get(self, request):
        return render(request, "player/rival.html")