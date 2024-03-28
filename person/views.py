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
    person_form_class = PublicForm
    template_name = "person/public.html"

    def get(self, request, *args, **kwargs):
        user_id = request.user.id
        person = Person.objects.get(user__id=user_id)
        person_form = self.person_form_class(
            initial = {
                'icon': request.user.person.icon,
                'name': request.user.person.name,
            },
            social_urls = self.social_urls(user_id),
        )
        context = {
            'person_form': person_form,
        }
        return render(request, self.template_name , context)

    def post(self, request, *args, **kwargs):
        user_id = request.user.id
        person_form = self.person_form_class(request.POST, social_urls=self.social_urls(user_id))
        if person_form.is_valid():
            person = Person.objects.get(user__id=user_id)
            person.icon = person_form.cleaned_data["icon"]
            person.name = form.cleaned_data["name"]
            person.save()
            for url in person_form.cleaned_data['socials']:
                social = Social.objects.get(person=person, url=url)
                social.delete()
            social_url = person_form.cleaned_data['social']
            if len(social_url) > 0:
                Social.objects.create(person=person, url=social_url)
            return HttpResponseRedirect("account/public/")
        context = {
            'person_form': person_form,
        }
        return render(request, self.template_name, context)

    def social_urls(self, user_id):
        urls = {}
        for url in Social.objects.filter(person__user__id=user_id):
            urls[url.url] = url.url
        return urls


class UpgradeView(View):

    def get(self, request):
        return render(request, "person/upgrade.html")

