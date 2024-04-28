from django.forms import BooleanField, CheckboxInput, CheckboxSelectMultiple, ChoiceField, Form, HiddenInput, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from player.forms import AcceptForm, KeenForm, RsvpForm
from player.models import Appeal, Friend, Invite
from qwikgame.utils import bytes3_to_str
from qwikgame.views import QwikView


class InviteView(QwikView):

    def get(self, request, *args, **kwargs):
        super().request_init(request)
        player = self.user.player
        context = {
            'appeals': Appeal.objects.filter(player=player).all(),
            'invites': Invite.objects.filter(rival=player).all(),
        }
        context |= super().context(request)
        if context['small_screen']:
            return render(request, "player/invite.html", context)
        else:
            return HttpResponseRedirect("/player/keen/")


class KeenView(QwikView):
    keen_form_class = KeenForm
    template_name = 'player/keen.html'

    def get(self, request, *args, **kwargs):
        super().get(request)
        player = self.user.player
        context = {
            'appeals': Appeal.objects.filter(player=player).all(),
            'invites': Invite.objects.filter(rival=player).all(),
        }
        context |= self.keen_form_class.get(player)
        context |= super().context(request)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        context = self.keen_form_class.post(
            request.POST,
            self.user.player,
        )
        if len(context) == 0:
            return HttpResponseRedirect("/player/invite/")
        context |= super().context(request)
        return render(request, self.template_name, context)


class InvitationView(QwikView):
    # invitation_form_class = InvitationForm
    template_name = 'game/invitation.html'

    def get(self, request, *args, **kwargs):
        super().get(request)
        player = self.user.player
        context = {
            'appeals': Appeal.objects.filter(player=player).all(),
            'invites': Invite.objects.filter(rival=player).all(),
        }
        context |= super().context(request)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        # context = self.invitation_form_class.post(
        #     request.POST,
        #     self.user.player,
        # )
        if len(context) == 0:
            return HttpResponseRedirect("/player/invite/keen/")
        context |= super().context(request)
        return render(request, self.template_name, context)


class ReplyView(QwikView):
    accept_form_class = AcceptForm
    template_name = 'player/reply.html'

    def get(self, request, *args, **kwargs):
        super().get(request)
        player = self.user.player
        appeal_pk = kwargs['appeal']
        appeal = Appeal.objects.get(pk=appeal_pk)
        appeals = Appeal.objects.filter(player=player).all()
        prev_pk = appeals.last().pk
        next_pk = appeals.first().pk
        found = False
        for a in appeals:
            if found:
                next_pk = a.pk
                break
            if a.pk == appeal.pk:
                found = True
            else:
                prev_pk = a.pk
        replies = Invite.objects.filter(appeal=appeal).exclude(hours=None)
        friends = Friend.objects.filter(player=player)
        for reply in replies:
            reply.hour_str = bytes3_to_str(reply.hours)
            try:
                reply.name = friends.get(rival=reply.rival).name
            except:
                reply.name=reply.rival.name
        context = {
            'appeal': appeal,
            'appeals': appeals,
            'invites': Invite.objects.filter(rival=player).all(),
            'next': next_pk,
            'prev': prev_pk,
            'replies': replies,
        }
        context |= self.accept_form_class.get()
        context |= super().context(request)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        context = self.accept_form_class.post(
            request.POST,
        )
        if len(context) == 0:
            return HttpResponseRedirect("/game/match/")
        return HttpResponseRedirect("/player/keen/{}/".format(kwargs['appeal']))


class RsvpView(QwikView):
    rsvp_form_class = RsvpForm
    template_name = 'player/rsvp.html'

    def get(self, request, *args, **kwargs):
        super().get(request)
        player = self.user.player
        invite_pk = kwargs['invite']
        invite = Invite.objects.get(pk=invite_pk)
        invites = Invite.objects.filter(rival=player).all()
        prev_pk = invites.last().pk
        next_pk = invites.first().pk
        found = False
        for a in invites:
            if found:
                next_pk = a.pk
                break
            if a.pk == invite.pk:
                found = True
            else:
                prev_pk = a.pk
        context = {
            'appeals': Appeal.objects.filter(player=player).all(),
            'invite': invite,
            'invites': invites,
            'next': next_pk,
            'prev': prev_pk,
        }
        context |= self.rsvp_form_class.get(invite)
        context |= super().context(request)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        player = self.user.player
        invite_pk = kwargs['invite']
        invite = Invite.objects.get(pk=invite_pk)
        context = self.rsvp_form_class.post(
            request.POST,
            invite,
        )
        if len(context) == 0:
            return HttpResponseRedirect("/player/invite/")
        invites = Invite.objects.filter(rival=player).all()
        prev_pk = invites.last().pk
        next_pk = invites.first().pk
        found = False
        for a in invites:
            if found:
                next_pk = a.pk
                break
            if a.pk == invite.pk:
                found = True
            else:
                prev_pk = a.pk
        context = {
            'appeals': Appeal.objects.filter(player=player).all(),
            'invite': invite,
            'invites': invites,
            'next': next_pk,
            'prev': prev_pk,
        }
        context |= super().context(request)
        return render(request, self.template_name, context)


class RivalView(QwikView):

    def get(self, request, *args, **kwargs):
        super().request_init(request)
        context = super().context(request)
        return render(request, "player/rival.html", context)