import logging
from django.contrib.auth import logout
from django.contrib.sites.shortcuts import get_current_site
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from django.views import View
from authenticate.models import User
from person.models import Block, Person, Social
from person.forms import BlockForm, UnblockForm, PrivateForm, PublicForm, SocialForm
from player.models import Player
from qwikgame.settings import LANGUAGES, LANGUAGE_COOKIE_NAME
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


class PrivateView(QwikView):
    private_form_class = PrivateForm
    template_name = "person/private.html"

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = self.private_form_class.get(self.user.person)
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
        person = self.user.person
        context = self.private_form_class.post(request.POST, person)
        form = context.get('private_form')
        if form and not form.is_valid():
            context |= self.context(request, *args, **kwargs)
            return render(request, self.template_name, context)
        # delete blocked
        for del_pk in context.get('del_blocked'):
            junk = Block.objects.get(pk=del_pk)
            if junk:
                logger.info(f'Deleting block: {junk}')
                junk.delete()
            else:
                logger.warn(f'failed to delete block: {person} : {del_pk}')
        person.notify_email = context["notify_email"]
        person.notify_push = context["notify_push"]
        person.location_auto = context["location_auto"]
        person.language = context["language"]
        person.save()
        response = HttpResponseRedirect("/account/private/")
        if person.language in dict(LANGUAGES):
            response.set_cookie(LANGUAGE_COOKIE_NAME, person.language)
        else:
            response.delete_cookie(LANGUAGE_COOKIE_NAME)
        return response


class PublicView(QwikView):
    public_form_class = PublicForm
    template_name = "person/public.html"

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        context |= {
            'account_tab': 'selected',
            'public_checked': 'checked',
        }
        if self.is_player:
            context = context | {'player': self.user.player}
        self._context = context
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = self.context(request, *args, **kwargs)
        context |= self.public_form_class.get(self.user.person)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        context = self.context(request, *args, **kwargs)
        context |= self.public_form_class.post(request.POST, self.user.person)
        form = context.get('friend_form')
        if form and not form.is_valid():
            context |= self.context(request, *args, **kwargs)
            return render(request, self.template_name, context)
        person = context['person']
        person.name = context['name']
        person.save()
        for del_pk in context.get('del_social'):
            junk = Social.objects.get(pk=del_pk)
            if junk:
                logger.info(f'Deleting social: {junk}')
                junk.delete()
            else:
                logger.warn(f'failed to delete social: {player} : {del_pk}')
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


class SocialView(QwikView):
    form_class = SocialForm
    template_name = 'person/social.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        self._context = context
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        context |= self.form_class.get()
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        context = self.form_class.post(request.POST)
        form = context.get('form')
        if form and not form.is_valid():
            context |= self.context(request, *args, **kwargs)
            return render(request, self.template_name, context)
        try:
            person = self.user.person
            social, created = Social.objects.update_or_create(
                person = person,
                url = context.get('social')
            )
            if created:
                person.social.add(social)
                person.save()
        except:
            logger.exception(f'failed add social: {context}')
        return HttpResponseRedirect("/account/public/")


class UpgradeView(QwikView):

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = super().context(request)
        return render(request, "person/upgrade.html", context)

