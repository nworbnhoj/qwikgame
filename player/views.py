import logging
from django.forms import BooleanField, CheckboxInput, CheckboxSelectMultiple, ChoiceField, Form, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, redirect, render
from player.forms import AcceptForm, FilterForm, KeenForm, RsvpForm, ScreenForm
from player.models import Appeal, Filter, Friend, Invite
from qwikgame.hourbits import Hours24x7
from api.models import Region, Mark
from service.locate import Locate
from venue.models import Venue
from qwikgame.views import QwikView
from qwikgame.widgets import DAY_ALL, DAY_NONE, WEEK_ALL, WEEK_NONE

logger = logging.getLogger(__file__)

class FilterView(QwikView):
    filter_form_class = FilterForm
    hide=[]
    template_name = 'player/filter.html'

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = self.filter_form_class.get(
            self.user.player,
            game='ANY',
            venue='ANY',
            hours=WEEK_NONE,
        )
        context |= super().context(request)
        return render(request, self.template_name, context)


    def post(self, request, *args, **kwargs):
        super().post(request)
        context = self.filter_form_class.post(
            request.POST,
        )

        game = context.get('game')
        venue = context.get('venue')
        if not venue:
            placeid = context.get('placeid')
            if placeid:
                venue = Venue.from_placeid(placeid)
                if venue:
                    venue.save()
                    if game:
                        venue.games.add(game)
                        venue.save()
                        Mark(game=game, venue=venue, size=1).save()
        try:
            new_filter = Filter.objects.get_or_create(
                player=self.user.player,
                game=game,
                venue=venue)
            filter_hours = context['hours']
            venue_hours = venue.hours_open()
            if venue_hours:
                filter_hours = filter_hours & venue_hours
            new_filter[0].set_hours(filter_hours)
            new_filter[0].save()
            logger.info(f'Filter new: {new_filter[0]}')
            # update the Mark size
            mark = Mark.objects.filter(game=game, venue=venue).first()
            if mark:
                mark.save()
        except:
            logger.exception("failed to add filter")
        return HttpResponseRedirect("/player/")


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
            reply.hour_str = reply.hours24().as_str()
            try:
                reply.name = friends.get(rival=reply.rival).name
            except:
                reply.name=reply.rival.name
        context = {
            'appeal': appeal,
            'appeals': appeals,
            'invites': Invite.objects.filter(rival=player).all(),
            'next': next_pk,
            'player_id': player.facet(),
            'prev': prev_pk,
            'replies': replies,
        }
        context |= self.accept_form_class.get()
        context |= super().context(request)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        player = self.user.player
        context = self.accept_form_class.post(
            request.POST,
            player,
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
            'player_id': player.facet(),
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


class ScreenView(QwikView):
    screen_form_class = ScreenForm
    template_name = 'player/screen.html'

    def get(self, request, *args, **kwargs):
        super().get(request)
        context = super().context(request)
        context |= self.screen_form_class.get(self.user.player)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request)
        context = self.screen_form_class.post(
            request.POST,
            self.user.player,
        )
        return HttpResponseRedirect("/player/")