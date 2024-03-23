from django.http import HttpResponse
from django.shortcuts import get_object_or_404, render
from django.views import View

from person.models import LANGUAGE, Person, Social
from person.forms import PublicForm

class AccountView(View):

    def get(self, request):
        return render(request, "person/account.html")



class PrivacyView(View):

    def get(self, request):
        return render(request, "person/privacy.html")


class PrivateView(View):

    def get(self, request):
        user_id = request.user.id
        person = Person.objects.get(user__id=user_id)
        languages = dict(LANGUAGE)
        context = {
            "email": request.user.email,
            "language": person.language,
            "languages": languages,
            "location_auto": "checked" if person.location_auto else "",
            "notify_email": "checked" if person.notify_email else "",
            "notify_web": "checked" if person.notify_web else "",
        }
        return render(request, "person/private.html", context)


class PublicView(View):

    def get(self, request):
        user_id = request.user.id
        if request.method == "POST":
            form = PublicForm(request.POST)
            if form.is_valid():
                return HttpResponseRedirect("/account/public/")
        else:
            form = PublicForm(
                initial = {
                    'icon': request.user.person.icon,
                    'name': request.user.person.name,
                },
                social = Social.objects.filter(person__user__id=user_id),
            )
        person = Person.objects.get(user__id=user_id)
        languages = dict(LANGUAGE)
        context = {
            'person_form': form,
        }
        return render(request, "person/public.html", context)


class UpgradeView(View):

    def get(self, request):
        return render(request, "person/upgrade.html")

