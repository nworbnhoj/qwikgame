from django import forms
from django.forms import BooleanField, CharField, CheckboxSelectMultiple, Form, MultipleChoiceField, URLField

from person.models import Person, Social


class DeleteWidget(CheckboxSelectMultiple):
    option_template_name="input_delete.html"


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
