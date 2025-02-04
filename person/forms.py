from django import forms
from django.forms import BooleanField, CharField, CheckboxSelectMultiple, ChoiceField, Form, IntegerField, MultipleChoiceField, Select, URLField
from person.models import LANGUAGE, Person, Social
from qwikgame.fields import MultipleActionField
from qwikgame.forms import QwikForm

class DeleteWidget(CheckboxSelectMultiple):
    option_template_name="input_delete.html"

class BlockForm(QwikForm):
    block = IntegerField(
        label='Person to block',
        required=True,
        )

    @classmethod
    def get(klass):
        return { 'block_form': klass() }


    @classmethod
    def post(klass, request_post):
        form = klass(data=request_post)
        context = { 'filter_form': form }
        if form.is_valid():
            context['block'] = form.cleaned_data['block']
        return context
    

class PrivateForm(QwikForm):
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
            'private_form': klass(
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
        private_form = klass(data=request_post)
        if private_form.is_valid():
            person.notify_email = private_form.cleaned_data["notify_email"]
            person.notify_web = private_form.cleaned_data["notify_web"]
            person.location_auto = private_form.cleaned_data["location_auto"]
            person.language = private_form.cleaned_data["language"]
            person.save()
        else:
            context = {'private_form': private_form}
        return context


class PublicForm(QwikForm):
    icon = CharField(
        label='PROFILE PICTURE',
        max_length=32,
        required = False,
        template_name='field.html',
    )
    name = CharField(
        label='QWIK NAME',
        max_length=32,
        required=False,
        template_name='field.html',
    )
    socials = MultipleChoiceField(
        choices=(),
        label='WEBSITE / SOCIAL MEDIA',
        required=False,
        template_name='field.html',
        widget=CheckboxSelectMultiple()
    )
    social = URLField(
        required=False,
        template_name="field_naked.html"
    )

    def __init__(self, *args, **kwargs):
        social_urls = kwargs.pop('social_urls')
        super(PublicForm, self).__init__(*args, **kwargs)
        self.fields['icon'].sub_text = 'Change (coming soon)'
        self.fields['icon'].url = ''
        self.fields['icon'].widget.attrs['placeholder'] = "your qwikgame icon"
        self.fields['icon'].widget.attrs['class'] = "hidden"
        self.fields['name'].widget.attrs['placeholder'] = "your qwikgame screen name"
        self.fields['social'].widget.attrs['placeholder'] = "add a social media url"
        self.fields['socials'].choices = social_urls
        self.fields['socials'].widget.attrs['class'] = "post"
        self.fields['socials'].widget.option_template_name='option_delete.html'

    # Initializes a PublicForm for 'person'.
    # Returns a context dict including 'public_form'
    @classmethod
    def get(klass, person):
        return {
            'public_form': klass(
                    initial = {
                        'icon': person.icon,
                        'name': person.qwikname,
                    },
                    social_urls = klass.social_urls(person.user.id),
                ),
        }

    # Initializes a PublicForm with 'request_post' for 'person'.
    # Returns a context dict including 'public_form' if form is not valid
    @classmethod
    def post(klass, request_post, person):
        context = {}
        public_form = klass(data=request_post, social_urls = klass.social_urls(person.user.id))
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

    @classmethod
    def social_urls(klass, user_id):
        urls = {}
        for url in Social.objects.filter(person__user__id=user_id):
            urls[url.url] = url.url
        return urls


class UnblockForm(QwikForm):
    blocked = MultipleActionField(
        action='unblock:',
        help_text='When you block a person, neither of you will see the other on qwikgame.',
        label='LIST OF BLOCKED PEOPLE',
        required=False,
    )

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields['blocked'].widget.option_template_name='option_delete.html'
        
    @classmethod
    def get(klass, person):
        form = klass()
        form.fields['blocked'].choices = klass.blocked_choices(person)
        return {
            'blocked_form': form,
        }

    @classmethod
    def post(klass, request_post, person):
        context = {}
        user_id = person.user.id
        form = klass(data=request_post)
        form.fields['blocked'].choices = klass.blocked_choices(person)
        if form.is_valid():
            for unblock in form.cleaned_data['blocked']:
                person.block.remove(unblock)
            person.save()
        else:
            context = {  
                'blocked_form': form,
            }
        return context

    @classmethod
    def blocked_choices(klass, person):
        choices={}
        for block in person.block.all():
            choices[block.pk] = block.name
        return choices
