from django import forms
from django.forms import BooleanField, CharField, CheckboxSelectMultiple, ChoiceField, ComboField, Field, Form,ModelMultipleChoiceField, MultipleChoiceField, MultiValueField, MultiWidget, Textarea

from person.models import Person
from player.models import Precis


class ActionMultiple(CheckboxSelectMultiple):
    attrs = {"class": "down hidden"}


class MultipleActionField(MultipleChoiceField):
    widget = ActionMultiple

    def __init__(self, action='delete:', *args, **kwargs):
        self.action = action
        super().__init__(*args, **kwargs)
        self.widget.attrs={"class": "down hidden"}
        self.template_name='dropdown.html'


class BlockedForm(Form):
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
        form = klass(request_post)
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


class PublicForm(Form):
    pass


class PrecisForm(Form):

    class Meta:
        error_messages = {
            'precis': {
                "max_length": "This precis is too long.",
            },
        }
        help_texts = {
            'precis': "Some useful help text.",
        }
        placeholders = {
            'precis': "hope"
        }

    def __init__(self, *args, **kwargs):
        game_precis = kwargs.pop('game_precis')
        super(PrecisForm, self).__init__(*args, **kwargs)
        hidden=False
        for game, precis in game_precis.items():
            name = game.code
            self.fields[name] = CharField(initial=precis.text, required = False, template_name="input_tab.html", widget=Textarea())
            self.fields[name].help_text = "Each precis is limited to 512 characters."
            self.fields[name].widget.attrs['placeholder'] = "Let rivals know why they want to play you."
            self.fields[name].widget.attrs['hidden'] = hidden
            hidden = True

    # Initializes a PublicForm with 'request_post' for 'player'.
    # Returns a context dict including 'precis_form' 'precis' & 'reputation'
    @staticmethod
    def get(player):
        return {
            'precis_form': PrecisForm(
                    game_precis = game_precis(player.user.id)
                ),
            'precis': Precis.objects.filter(player__user__id=player.user.id),
            'reputation': player.reputation(),
        }

    # Initializes a PublicForm for 'player'.
    # Returns a context dict including 'precis_form' 'precis' & 'reputation'
    @staticmethod
    def post(request_post, player):
        context = {}
        user_id = player.user.id
        precis_form = PrecisForm(request_post, game_precis = game_precis(user_id))
        if precis_form.is_valid():
            for game_code, field in precis_form.fields.items():
                precis = Precis.objects.get(game=game_code, player=player)
                precis.text = precis_form.cleaned_data[game_code]
                precis.save()
        else:
            context = {      
                'precis': Precis.objects.filter(player__user__id=user_id),
                'precis_form': precis_form,
                'reputation': player.reputation(),
            }
        return context


def game_precis(user_id):
    gp = {}
    for precis in Precis.objects.filter(player__user__id=user_id):
        gp[precis.game] = precis
    return gp