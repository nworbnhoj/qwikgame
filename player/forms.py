from django import forms
from django.forms import BooleanField, CharField, CheckboxSelectMultiple, ComboField, Field, Form, MultipleChoiceField, MultiValueField, MultiWidget, Textarea

from person.models import Person


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