from django import forms
from django.forms import BooleanField, CharField, CheckboxSelectMultiple, ChoiceField, Form, IntegerField, MultipleChoiceField, Select, URLField
from django.utils.translation import gettext_lazy as _
from person.models import LANGUAGE, ALERT_EMAIL_DEFAULT, ALERT_PUSH_DEFAULT, Alert, Person, Social
from qwikgame.fields import MultipleActionField
from qwikgame.forms import QwikForm

class DeleteWidget(CheckboxSelectMultiple):
    option_template_name="input_delete.html"

class BlockForm(QwikForm):
    block = IntegerField(
        label=_('Person to block'),
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
        label=_('EMAIL NOTIFICATIONS'),
        required=False,
        template_name='input_checkbox.html'
    )
    notify_push = BooleanField(
        label=_('WEB / APP NOTIFICATIONS'),
        required=False,
        template_name='input_webpush.html'
    )
    location_auto = BooleanField(
        help_text=_("This will make finding a Venue nearby faster."),
        label=_('ALLOW LOCATION ACCESS'),
        required=False,
        template_name="input_checkbox.html"
    )
    language = ChoiceField(
        choices = dict(LANGUAGE), 
        label=_('LANGUAGE'), 
        template_name='field.html', 
    )

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    # Initializes a PrivateForm for 'person'.
    # Returns a context dict including 'private_form'
    @classmethod
    def get(klass, person):
        form = klass(
            initial = {
                'notify_email': bool(person.notify_email),
                'notify_push': bool(person.notify_push),
                'location_auto': person.location_auto,
                'language': person.language,
            },
        )
        form.fields['notify_email'].help_text = _("Receive Email for Bid %(bid)s and Match %(match)s") % {
                "bid": Alert.str(True, ALERT_EMAIL_DEFAULT, 'bid', 'email'),
                "match": Alert.str(True, ALERT_EMAIL_DEFAULT, 'match', 'email')
            }
        form.fields['notify_push'].help_text = _("See App Notifications for Bid %(bid)s and Match %(match)s)") % {
                "bid": Alert.str(True, ALERT_PUSH_DEFAULT, 'bid', 'push'),
                "match": Alert.str(True, ALERT_PUSH_DEFAULT, 'match', 'push')
            }
        return {'private_form': form }

    # Initializes a PrivateForm with 'request_post' for 'person'.
    # Returns a context dict including 'private_form' if form is not valid
    @classmethod
    def post(klass, request_post, person):
        context = {}
        private_form = klass(data=request_post)
        if private_form.is_valid():
            person.notify_email = ALERT_EMAIL_DEFAULT if private_form.cleaned_data["notify_email"] else ''
            person.notify_push = ALERT_PUSH_DEFAULT if private_form.cleaned_data["notify_push"] else ''
            person.location_auto = private_form.cleaned_data["location_auto"]
            person.language = private_form.cleaned_data["language"]
            person.save()
        else:
            context = {'private_form': private_form}
        return context


class PublicForm(QwikForm):
    name = CharField(
        label=_('QWIK NAME'),
        max_length=32,
        required=False,
        template_name='field.html',
    )
    socials = MultipleChoiceField(
        choices=(),
        label=_('WEBSITE / SOCIAL MEDIA'),
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
        self.fields['name'].widget.attrs['placeholder'] = _('your qwikgame screen name')
        self.fields['social'].widget.attrs['placeholder'] = _('add a social media url')
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
    blocked = MultipleChoiceField(
        help_text=_('When you block a person, neither of you will see the other on qwikgame.'),
        label=_('PEOPLE YOU HAVE BLOCKED'),
        widget=CheckboxSelectMultiple,
    )
        
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
