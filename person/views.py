import logging
from django.contrib.auth import logout
from django.contrib.sites.shortcuts import get_current_site
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from django.views import View
from authenticate.models import User
from person.models import Block, Person
from person.forms import BlockForm, UnblockForm, PrivateForm, PublicForm
from player.models import Player
from qwikgame.views import QwikView


logger = logging.getLogger(__file__)


class AccountView(QwikView):

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = super().context(request)
        player = self.user.player        
        player.alert_del(type='')
        context |= { 'account_tab': 'selected' }
        if context['small_screen']:
            return render(request, "person/account.html", context)
        else:
            return HttpResponseRedirect("/account/public/")


class BlockView(QwikView):
    block_form_class = BlockForm

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        block_pk = kwargs.get('block')
        block_person = Person.objects.filter(pk=block_pk).first()
        if block_person:
            Block.objects.get_or_create(
                person=self.user.person,
                blocked=block_person,
            )
            # currently BlockForm.get() is only used to block unwelcome email
            # invitations to unregistered Players, so logout immediately
            logout(self.request)
            block_player = block_person.user.player
            context = { 'blocked_player': block_player }
            return render(request, 'person/block_done.html', context)
        return HttpResponseRedirect("/appeal")


class NotifyEmailView(QwikView):
    # notify_email_off_class = NotifyEmailForm

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        self.user.notify_email = kwargs.get('notify', 1) != 0
        self.user.save()
        self.user.person.notify_email = kwargs.get('notify', 1) != 0
        self.user.person.save()
        # currently NotifyEmailForm.get() is only used to block unwelcome email
        # invitations to unregistered Players, so logout immediately
        logout(self.request)
        context = {'site_name': get_current_site(request).name }
        return render(request, 'person/notify_email_done.html', context )


class PrivacyView(QwikView):

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = super().context(request)
        context |= { 
            'account_tab': 'selected',
            'privacy_checked': 'checked',
        }
        return render(request, "person/privacy.html", context)


class PrivateView(QwikView):
    private_form_class = PrivateForm
    blocked_form_class = UnblockForm
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
        context |= {
            'account_tab': 'selected',
            'private_checked': 'checked',
        }
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        if 'unblock' in request.POST:
            context = self.blocked_form_class.post(request.POST, self.user.person)
        else:
            context = self.private_form_class.post(request.POST, self.user.person)
        if len(context) == 0:
            return HttpResponseRedirect("/account/private/")
        context = context | super().context(request)
        return render(request, self.template_name, context)


class PublicView(QwikView):
    public_form_class = PublicForm
    template_name = "person/public.html"

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = self.public_form_class.get(self.user.person)
        if self.is_player:
            context = context | {}
        if self.is_manager:
            manager = self.user.manager
            context = context | {}
        context = context | super().context(request)
        context |= {
            'account_tab': 'selected',
            'public_checked': 'checked',
        }
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        context = self.public_form_class.post(request.POST, self.user.person)
        if self.is_player:
            context = context | {}
        if len(context) == 0:
            return HttpResponseRedirect("/account/public/")
        context = context | super().context(request)
        return render(request, self.template_name, context)


class PWAView(QwikView):

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = super().context(request)
        context |= { 
            'account_tab': 'selected',
            'pwa_checked': 'checked',
        }
        return render(request, "person/pwa.html", context)


class TermsView(QwikView):

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = super().context(request)
        context |= {
            'account_tab': 'selected',
            'terms_checked': 'checked',
        }
        return render(request, "person/terms.html", context)


class UpgradeView(QwikView):

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = super().context(request)
        return render(request, "person/upgrade.html", context)

