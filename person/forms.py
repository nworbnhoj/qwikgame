from django import forms
from django.forms import BooleanField, CharField, CheckboxSelectMultiple, ChoiceField, EmailField, Form, IntegerField, MultipleChoiceField, RadioSelect, Select, URLField
from django.utils.translation import gettext_lazy as _
from person.models import ALERT_EMAIL_DEFAULT, ALERT_PUSH_DEFAULT, Alert, Block, Person, Social
from qwikgame.fields import MultipleActionField
from qwikgame.forms import QwikForm
from qwikgame.settings import LANGUAGES

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
    email = EmailField(
        label = _("EMAIL ADDRESS"),
        max_length=255,
        required = False,
        template_name = 'field.html',
        widget=forms.TextInput(attrs={'disabled': 'disabled'}),
    )   
    permissions = MultipleChoiceField(
        choices=[
            ('email', _('email notifications')),
            ('push', _('push notifications')),
            ('location', _('location access'))
        ],
        help_text=_('Your choice.'),
        label=_('PERMISSIONS'),
        required=False,
        template_name='field.html',
        widget=CheckboxSelectMultiple()
    )
    language = ChoiceField(
        choices = [('', _("Browser default"))] + LANGUAGES, 
        label=_('LANGUAGE'),
        required=False,
        template_name='field.html',
        widget = RadioSelect,
    )
    blocked = MultipleChoiceField(
        choices=(),
        help_text=_('When you block a person, neither of you will see the other on qwikgame.'),
        label=_('PEOPLE YOU HAVE BLOCKED'),
        required=False,
        template_name='field.html',
        widget=CheckboxSelectMultiple()
    )

    def __init__(self, *args, **kwargs):
        blocked_choices = kwargs.pop('blocked_choices')
        super(QwikForm, self).__init__(*args, **kwargs)
        self.fields['blocked'].choices = blocked_choices
        self.fields['blocked'].widget.attrs['class'] = "post"
        self.fields['blocked'].widget.option_template_name='option_delete.html'

    @classmethod
    def _blocked_choices(klass, person):
        return [(b.pk, b.blocked.name) for b in Block.objects.filter(person=person)]

    @classmethod
    def get(klass, person):
        form = klass(
            blocked_choices = klass._blocked_choices(person),
            initial = {
                'email': person.user.email,
                'language': person.language,
                'permissions': [
                    'email' if person.notify_email else '',
                    'push' if person.notify_push else '',
                    'location' if person.location_auto else ''
                    ],
            },
        )
        form.fields['email'].sub_text = ' '
        form.fields['permissions'].sub_text = ' '
        form.fields['language'].sub_text = ' '
        form.fields['blocked'].sub_text = ' '
        return {'private_form': form }

    @classmethod
    def post(klass, request_post, person):
        form = klass(
            blocked_choices = klass._blocked_choices(person),
            data=request_post
        )
        context = {'private_form': form}
        if form.is_valid():
            permissions = form.cleaned_data['permissions']
            context |= {
                'del_blocked': form.cleaned_data["blocked"],
                'notify_email': ALERT_EMAIL_DEFAULT if 'email' in permissions else '',
                'notify_push': ALERT_PUSH_DEFAULT if 'push' in permissions else '',
                'location_auto': 'location' in permissions,
                'language': form.cleaned_data["language"],
            }
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

    def __init__(self, *args, **kwargs):
        social_choices = kwargs.pop('social_choices')
        super(PublicForm, self).__init__(*args, **kwargs)
        self.fields['name'].widget.attrs['placeholder'] = _('your qwikgame screen name')
        self.fields['socials'].choices = social_choices
        self.fields['socials'].widget.attrs['class'] = "post"
        self.fields['socials'].widget.option_template_name='option_delete.html'

    @classmethod
    def _social_choices(klass, user_id):
        return [(s.pk, s) for s in Social.objects.filter(person__user__id=user_id)]

    # Initializes a PublicForm for 'person'.
    # Returns a context dict including 'public_form'
    @classmethod
    def get(klass, person):
        form = klass(
            initial = { 'name': person.qwikname },
            social_choices = klass._social_choices(person.user.id),
        )
        form.fields['name'].sub_text = ' '
        form.fields['socials'].sub_text = ' '
        return { 'public_form': form }

    # Initializes a PublicForm with 'request_post' for 'person'.
    # Returns a context dict including 'public_form' if form is not valid
    @classmethod
    def post(klass, request_post, person):
        context = {}
        form = klass(
            data=request_post,
            social_choices = klass._social_choices(person.user.id)
        )
        context = { 'public_form': form }
        if form.is_valid():
            context |= {
                'name': form.cleaned_data['name'],
                'del_social': form.cleaned_data['socials']
            }
        return context


class SocialForm(QwikForm):
    social = URLField(
        required = True,
        template_name='field.html',
    )

    @classmethod
    def get(klass, strength=None):
        form = klass()
        form.fields['social'].sub_text = ' '
        return { 'social_form' : form, }

    @classmethod
    def post(klass, request_post):
        form = klass(data=request_post)
        context = {'form': form}
        if form.is_valid():
            context |= {
                'social': form.cleaned_data['social'],
            }
        return context


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
        disabled = 'disabled' if len(form.fields['blocked'].choices) == 0 else ''
        return {
            'blocked_form': form,
            'unblock_disabled': disabled,
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
