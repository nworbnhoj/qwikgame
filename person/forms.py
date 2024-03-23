from django import forms
from django.forms import BooleanField, CharField, CheckboxSelectMultiple, Form, MultipleChoiceField, URLField

from person.models import Person


class DeleteWidget(CheckboxSelectMultiple):
    option_template_name="input_delete.html"


class PublicForm(Form):
    icon = CharField(label='PROFILE PICTURE', max_length=32, template_name="input_icon.html")
    name = CharField(label='NAME OR NICK', max_length=32, template_name="input_text.html")
    socials = MultipleChoiceField(choices = (), label='WEBSITE / SOCIAL MEDIA', template_name="input_multi.html", widget=DeleteWidget() )
    social = URLField(template_name="input_naked.html")

    class Meta:
        error_messages = {
            'name': {
                "max_length": "This name is too long.",
            },
        }
        help_texts = {
            'name': "Some useful help text.",
        }
        placeholders = {
            'name': "hope"
        }

    def __init__(self, *args, **kwargs):
        social = kwargs.pop('social')
        super(PublicForm, self).__init__(*args, **kwargs)
        self.fields['icon'].widget.attrs['placeholder'] = "your qwikgame icon"
        self.fields['name'].widget.attrs['placeholder'] = "your qwikgame screen name"
        self.fields['social'].widget.attrs['placeholder'] = "add a social media url"
        for url in social:
            self.fields['socials'].widget.choices.append((url, url))