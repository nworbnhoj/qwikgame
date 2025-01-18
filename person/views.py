from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from django.views import View
from authenticate.models import User
from person.models import Person
from person.forms import BlockedForm, PrivateForm, PublicForm
from player.models import Player, Precis
from player.forms import PrecisForm
from qwikgame.views import QwikView


class AccountView(QwikView):

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = super().context(request)
        player = self.user.player        
        player.alert_del(type='account')
        context |= { 'account_tab': 'selected' }
        if context['small_screen']:
            return render(request, "person/account.html", context)
        else:
            return HttpResponseRedirect("/account/public/")


class PrivacyView(QwikView):

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = super().context(request)
        context |= { 'account_tab': 'selected' }
        return render(request, "person/privacy.html", context)


class PrivateView(QwikView):
    private_form_class = PrivateForm
    blocked_form_class = BlockedForm
    template_name = "person/private.html"

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = self.private_form_class.get(self.user.person)
        context |= self.blocked_form_class.get(self.user.person)
        if self.is_player:
            player = self.user.player
            context = context | {}
        if self.is_manager:
            manager = self.user.manager
            context = context | {}
        context = context | {
            'email': request.user.email,
        }
        context = context | super().context(request)
        context |= { 'account_tab': 'selected' }
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        context = self.private_form_class.post(request.POST, self.user.person)
        context |= self.blocked_form_class.post(request.POST, self.user.person)
        if len(context) == 0:
            return HttpResponseRedirect("/account/private/")
        context = context | super().context(request)
        return render(request, self.template_name, context)


class PublicView(QwikView):
    public_form_class = PublicForm
    precis_form_class = PrecisForm
    template_name = "person/public.html"

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = self.public_form_class.get(self.user.person)
        if self.is_player:
            context = context | self.precis_form_class.get(self.user.player)
        if self.is_manager:
            manager = self.user.manager
            context = context | {}
        context = context | super().context(request)
        context |= { 'account_tab': 'selected' }
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        context = self.public_form_class.post(request.POST, self.user.person)
        if self.is_player:
            context = context | self.precis_form_class.post(request.POST, self.user.player)
        if len(context) == 0:
            return HttpResponseRedirect("/account/public/")
        context = context | super().context(request)
        return render(request, self.template_name, context)


class UpgradeView(QwikView):

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = super().context(request)
        return render(request, "person/upgrade.html", context)

