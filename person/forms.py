from django import forms
from django.forms import BooleanField, CharField, CheckboxSelectMultiple, ChoiceField, Form, MultipleChoiceField, Select, URLField

from person.models import LANGUAGE, Person, Social


class DeleteWidget(CheckboxSelectMultiple):
    option_template_name="input_delete.html"



class PrivateForm(Form):
    notify_email = BooleanField(
        label='Email notifications',
        required=False,
        template_name="input_checkbox.html"
    )
    notify_web = BooleanField(
        label='Web / App notifications',
        required=False,
        template_name="input_checkbox.html"
    )
    location_auto = BooleanField(
        help_text="This will make finding a Venue nearby faster.",
        label='ALLOW LOCATION ACCESS',
        required=False,
        template_name="input_checkbox.html"
    )
    language = ChoiceField(
        choices = dict(LANGUAGE), 
        label='LANGUAGE', 
        template_name='dropdown.html', 
        widget=forms.RadioSelect(attrs={"class": "down hidden"})
    )

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    # Initializes a PrivateForm for 'person'.
    # Returns a context dict including 'private_form'
    @classmethod
    def get(klass, person):
        return {
            'private_form': PrivateForm(
                initial = {
                    'notify_email': person.notify_email,
                    'notify_web': person.notify_web,
                    'location_auto': person.location_auto,
                    'language': person.language,
                },
            ),
        }

    # Initializes a PrivateForm with 'request_post' for 'person'.
    # Returns a context dict including 'private_form' if form is not valid
    @classmethod
    def post(klass, request_post, person):
        context = {}
        private_form = PrivateForm(request_post)
        if private_form.is_valid():
            person.notify_email = private_form.cleaned_data["notify_email"]
            person.notify_web = private_form.cleaned_data["notify_web"]
            person.location_auto = private_form.cleaned_data["location_auto"]
            person.language = private_form.cleaned_data["language"]
            person.save()
        else:
            context = {'private_form': private_form}
        return context


class PublicForm(Form):
    icon = CharField(label='PROFILE PICTURE', max_length=32, required = False, template_name="input_icon.html")
    name = CharField(label='NAME OR NICK', max_length=32, required = False, template_name="input_text.html")
    socials = MultipleChoiceField(choices = (), label='placeholder', required = False)
    social = URLField(required = False, template_name="input_naked.html")

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
        social_urls = kwargs.pop('social_urls')
        super(PublicForm, self).__init__(*args, **kwargs)
        self.fields['icon'].widget.attrs['placeholder'] = "your qwikgame icon"
        self.fields['name'].widget.attrs['placeholder'] = "your qwikgame screen name"
        self.fields['social'].widget.attrs['placeholder'] = "add a social media url"
        choices=[]
        for url in social_urls:
            choices.append((url, url))
        self.fields['socials'] = MultipleChoiceField(choices = choices, label='WEBSITE / SOCIAL MEDIA', required = False, template_name="input_multi.html", widget=DeleteWidget() )

    # Initializes a PublicForm for 'person'.
    # Returns a context dict including 'public_form'
    @staticmethod
    def get(person):
        return {
            'public_form': PublicForm(
                    initial = {
                        'icon': person.icon,
                        'name': person.name,
                    },
                    social_urls = social_urls(person.user.id),
                ),
        }

    # Initializes a PublicForm with 'request_post' for 'person'.
    # Returns a context dict including 'public_form' if form is not valid
    @staticmethod
    def post(request_post, person):
        context = {}
        public_form = PublicForm(request_post, social_urls = social_urls(person.user.id))
        if public_form.is_valid():
            person.icon = public_form.cleaned_data["icon"]
            person.name = public_form.cleaned_data["name"]
            person.save()            
            for url in public_form.cleaned_data['socials']:
                social = Social.objects.get(person=person, url=url)
                social.delete()
            social_url = public_form.cleaned_data['social']
            if len(social_url) > 0:
                Social.objects.create(person=person, url=social_url)
        else:
            context = {'public_form': public_form}
        return context



def social_urls(user_id):
    urls = {}
    for url in Social.objects.filter(person__user__id=user_id):
        urls[url.url] = url.url
    return urls
