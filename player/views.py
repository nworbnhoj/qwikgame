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
    person_form_class = PersonPublicForm
    player_form_class = PublicForm
    precis_form_class = PrecisForm
    template_name = "player/public.html"

    @method_decorator(login_required)
    def dispatch(self, *args, **kwargs):
        return super().dispatch(*args, **kwargs)

    def get(self, request, *args, **kwargs):
        user_id = request.user.id
        person = Person.objects.get(user__id=user_id)
        player = Player.objects.get(user__id=user_id)
        person_form = self.person_form_class(
            initial = {
                'icon': request.user.person.icon,
                'name': request.user.person.name,
            },
            social_urls = self.social_urls(user_id),
        )
        player_form = self.player_form_class()
        precis_form = self.precis_form_class(
            game_precis = self.game_precis(user_id)
        )
        context = {
            'person_form': person_form,
            'player_form': player_form,
            'precis_form': precis_form,
            'precis': Precis.objects.filter(player__user__id=user_id),
            'reputation': player.reputation(),
        }
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        user_id = request.user.id
        person = Person.objects.get(user__id=user_id)
        player = Player.objects.get(user__id=user_id)        
        person_form = self.person_form_class(request.POST, social_urls = self.social_urls(user_id))
        player_form = self.player_form_class(request.POST)
        precis_form = self.precis_form_class(request.POST, game_precis = self.game_precis(user_id))
        if person_form.is_valid() and player_form.is_valid() and precis_form.is_valid():
            person.icon = person_form.cleaned_data["icon"]
            person.name = person_form.cleaned_data["name"]
            person.save()
            for url in person_form.cleaned_data['socials']:
                social = Social.objects.get(person=person, url=url)
                social.delete()
            social_url = person_form.cleaned_data['social']
            if len(social_url) > 0:
                Social.objects.create(person=person, url=social_url)
            for game_code, field in precis_form.fields.items():
                precis = Precis.objects.get(game=game_code, player=player)
                precis.text=precis_form.cleaned_data[game_code]
                precis.save()
            return HttpResponseRedirect("/player/account/public/")
        context = {
            'person_form': person_form,
            'player_form': player_form,
            'precis_form': precis_form,
            'precis': Precis.objects.filter(player__user__id=user_id),
            'reputation': player.reputation(),
        }
        return render(request, self.template_name, context)

    def social_urls(self, user_id):
        urls = {}
        for url in Social.objects.filter(person__user__id=user_id):
            urls[url.url] = url.url
        return urls

    def game_precis(self, user_id):
        gp = {}
        for precis in Precis.objects.filter(player__user__id=user_id):
            gp[precis.game] = precis
        return gp


class UpgradeView(View):

    def get(self, request):
        return render(request, "player/upgrade.html")



class RivalView(View):

    def get(self, request):
        return render(request, "player/rival.html")