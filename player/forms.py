import datetime
from django.core.exceptions import ValidationError
from django.forms import BooleanField, CharField, CheckboxInput, CheckboxSelectMultiple, ChoiceField, Form, HiddenInput, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect, Textarea, TypedChoiceField
from django.utils import timezone
from game.models import Game
from person.models import Person
from player.models import Appeal, Friend, Player, Precis
from venue.models import Venue
from qwikgame.fields import ActionMultiple, DayField, MultipleActionField, MultiTabField, RangeField, SelectRangeField, TabInput, WeekField
from qwikgame.forms import QwikForm
from qwikgame.utils import bytes3_to_int, str_to_hours24


class BlockedForm(QwikForm):
    blocked = MultipleActionField(
        action='unblock:',
        help_text='When you block a player, neither of you will see the other on qwikgame.',
        label='LIST OF BLOCKED PLAYERS',
        required=False,
    )

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields['blocked'].widget.option_template_name='option_delete.html'
        
    # Initializes a PrivateForm for 'player'.
    # Returns a context dict including 'player_form'
    @classmethod
    def get(klass, player):
        form = klass()
        form.fields['blocked'].choices = klass.blocked_choices(player)
        return {
            'blocked_form': form,
        }

    # Initializes a PrivateForm for 'player'.
    # Returns a context dict including 'player_form'
    @classmethod
    def post(klass, request_post, player):
        context = {}
        user_id = player.user.id
        form = klass(data=request_post)
        form.fields['blocked'].choices = klass.blocked_choices(player)
        if form.is_valid():
            for unblock in form.cleaned_data['blocked']:
                player.blocked.remove(unblock)
            player.save()
        else:
            context = {  
                'blocked_form': form,
            }
        return context

    @classmethod
    def blocked_choices(klass, player):
        choices={}
        for blocked in player.blocked.all():
            choices[blocked.email_hash] = "{} ({})".format(blocked.name(), blocked.facet())
        return choices



class KeenForm(QwikForm):
    game = ChoiceField(
        choices = Game.choices(),
        label = 'GAME',
        template_name='dropdown.html', 
        widget=RadioSelect(attrs={"class": "down hidden"})
    )
    venue = ChoiceField(
        choices = {'map': 'Select from map'} | Venue.choices(),
        label='VENUE',
        template_name='dropdown.html', 
        widget=RadioSelect(attrs={"class": "down hidden"})
    )
    today = DayField(
        help_text='When are you keen to play?',
        label='TODAY',
        hours=[*range(6,21)],
        required=False,
        template_name='field.html'
    )
    tomorrow = DayField(
        help_text='When are you keen to play?',
        label='TOMORROW',
        hours=[*range(6,21)],
        required=False,
        template_name='field.html'
    )
    friends = MultipleActionField(
        action='invite:',
        help_text='Invite your friends to play this qwikgame.',
        label='FRIENDS',
        required=False,
    )
    strength = SelectRangeField(
        choices={'W':'Much Weaker', 'w':'Weaker', 'm':'Well Matched', 's':'Stronger', 'S':'Much Stronger'},
        help_text='Restrict this invitation to Rivals of a particular skill level.',
        label = "RIVAL'S SKILL LEVEL",
        required=False,
        template_name = 'upgrade.html',
        disabled=True, # TODO design Model for reckon strength against Venue/Region
    )

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    def clean(self):
        cleaned_data = super().clean()
        if cleaned_data.get('today') == b'\x00\x00\x00':
            if cleaned_data.get('tomorrow') == b'\x00\x00\x00':
                raise ValidationError(
                    'Please select at least one hour in today or tomorrow.'
                )

    def personalise(self, player):
        self.fields['friends'].choices = player.friend_choices()
        self.fields['friends'].sub_text = 'Add Friends'
        self.fields['friends'].url = 'friends'
        today = timezone.now()
        tomorrow = today + datetime.timedelta(days=1)
        self.fields['today'].sub_text = today.strftime('%A')
        self.fields['today'].help_text = 'What time are you keen to play today?'
        self.fields['tomorrow'].sub_text = tomorrow.strftime('%A')
        self.fields['tomorrow'].help_text = 'What time are you keen to play tomorrow?'


    # Initializes an KeenForm for 'player'.
    # Returns a context dict including 'keen_form'
    @classmethod
    def get(klass, player, game=None, hours=None, strength=None, venue='map'):
        form = klass(
                initial = {
                    'game': game,
                    'hours': hours,
                    'strength': strength,
                    'venue': venue,
                },
            )
        form.personalise(player)
        return {
            'keen_form': form,
        }

    # Initializes an KeenForm for 'player'.
    # Returns a context dict including 'keen_form'
    @classmethod
    def post(klass, request_post, player):
        context = {}
        form = klass(data=request_post)
        form.personalise(player)
        if form.is_valid():
            try:
                now=timezone.now()
                one_day=datetime.timedelta(days=1)
                friends = {Player.objects.get(pk=friend) for friend in form.cleaned_data['friends']}
                game=Game.objects.get(pk=form.cleaned_data['game'])
                venue=Venue.objects.get(pk=form.cleaned_data['venue'])
                # create/update/delete today appeal
                appeal = Appeal.objects.get_or_create(
                    date=now.date(),
                    game=game,
                    player=player,
                    venue=venue,
                )[0]
                if form.cleaned_data['today'] == b'\x00\x00\x00':
                    appeal.delete()
                elif appeal.hours != form.cleaned_data['today']:
                    appeal.hours = form.cleaned_data['today']
                    appeal.save()
                    appeal.invite_rivals(friends)
                # create/update/delete tomorrow appeal
                appeal = Appeal.objects.get_or_create(
                    date=(now + one_day).date(),
                    game=game,
                    player=player,
                    venue=venue,
                )[0]
                if form.cleaned_data['tomorrow'] == b'\x00\x00\x00':
                    appeal.delete()
                elif appeal.hours != form.cleaned_data['tomorrow']:
                    appeal.hours = form.cleaned_data['tomorrow']
                    appeal.save()
                    appeal.invite_rivals(friends)
            except:
                context = {'keen_form': form}
        else:
            context = {'keen_form': form}
        return context


class PublicForm(QwikForm):
    pass


class PrecisForm(QwikForm):

    def __init__(self, *args, **kwargs):
        precis = kwargs.pop('precis')
        super(PrecisForm, self).__init__(*args, **kwargs)
        fields, widgets, initial = {}, {}, []
        for p in precis:
            name = p.game.code
            widget = Textarea(
                attrs = {
                    'label': p.game.name,
                    'placeholder': "Let rivals know why they want to play you.",
                },
            )
            widgets[p.game.code] = widget
            fields[p.game.code] = CharField(label=p.game.name, required=False)
            initial.append(p.text)
        self.fields['precis'] = MultiTabField(
            fields,
            label='ABOUT',
            require_all_fields=False,
            template_name = 'field.html',
            widget=TabInput(widgets))
        self.fields['precis'].help_text = "Let rivals know why they want to play you."
        self.fields['precis'].initial = initial


    # Initializes a PublicForm with 'request_post' for 'player'.
    # Returns a context dict including 'precis_form' 'precis' & 'reputation'
    @classmethod
    def get(klass, player):
        return {
            'precis_form': PrecisForm(
                precis = Precis.objects.filter(player__user__id=player.user.id)
            ),
            'reputation': player.reputation(),
        }

    # Initializes a PublicForm for 'player'.
    # Returns a context dict including 'precis_form' 'precis' & 'reputation'
    @classmethod
    def post(klass, request_post, player):
        context = {}
        user_id = player.user.id
        precis_form = PrecisForm(
            data=request_post, 
            precis=Precis.objects.filter(player__user__id=player.user.id)
        )
        if precis_form.is_valid():
            for game_code, text in precis_form.cleaned_data['precis'].items():
                precis = Precis.objects.get(game=game_code, player=player)
                precis.text = text
                precis.save()
        else:
            context = {
                'precis_form': precis_form,
                'reputation': player.reputation(),
            }
        return context




class RsvpForm(QwikForm):
    hour = TypedChoiceField(
        coerce=str_to_hours24,
        help_text='When are you keen to play?',
        label='Pick Time to play',
        required=False,
        template_name='field_pillar.html',
        widget=RadioSelect,
    )

    # Initializes an RspvForm for an 'invite'.
    # Returns a context dict including 'rspv_form'
    @classmethod
    def get(klass, invite):
        form = klass( initial={'hour': bytes3_to_int(invite.hours)})
        form.fields['hour'].choices = invite.hour_choices()
        form.fields['hour'].sub_text = invite.appeal.date
        form.fields['hour'].widget.attrs = {'class': 'radio_block hour_grid'}
        form.fields['hour'].widget.option_template_name='input_hour.html'
        return {
            'rsvp_form': form,
        }

    # Initializes an Rsvp for an 'invite'.
    # Returns a context dict including 'rsvp_form'
    @classmethod
    def post(klass, request_post, invite):
        context = {}
        form = klass(data=request_post)
        form.fields['hour'].choices = invite.hour_choices()
        if form.is_valid():
            try:
                if 'accept' in request_post:
                    invite.hours = form.cleaned_data['hour']
                    invite.save()
                elif 'decline' in request_post:
                    invite.delete()
            except:
                context = {'rsvp_form': form}
        else:
            context = {'rsvp_form': form}
        return context