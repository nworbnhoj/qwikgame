import logging
from django.forms import BooleanField, CharField,  CheckboxInput, CheckboxSelectMultiple, ChoiceField, Form, HiddenInput, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from game.models import Game
from qwikgame.fields import ActionMultiple, MultipleActionField
from qwikgame.constants import STRENGTH, WEEK_DAYS
from venue.models import Venue
from qwikgame.forms import QwikForm
from qwikgame.fields import ActionMultiple, DayField, RangeField, SelectRangeField, MultipleActionField, WeekField
from qwikgame.log import Entry
from qwikgame.widgets import IconSelectMultiple

logger = logging.getLogger(__file__)


class ChatForm(QwikForm):
    txt = CharField(
        required = True,
        template_name = 'field_naked.html' #'input_chat.html'
    )

    # Initializes a ChatForm for a 'match'.
    # Returns a context dict including 'chat_form'
    @classmethod
    def get(klass):
        form = klass()
        form.fields['txt'].widget.attrs = { 'placeholder': 'Chat here with your rival'}
        return {
            'chat_form': form,
        }

    # Initializes an ChatForm for 'match'.
    # Returns a context dict including 'chat_form'
    @classmethod
    def post(klass, request_post):
        form = klass(data=request_post)
        context = { 'chat_form': form }
        if form.is_valid():
            context['txt'] = form.cleaned_data['txt']
        return context


class ReviewForm(QwikForm):
    conduct = ChoiceField(
        choices = {'good':'good', 'bad':'bad'},
        initial = 'good',
        label = 'CONDUCT',
        required = True,
        widget = RadioSelect,
    )
    strength = ChoiceField(
        choices = STRENGTH,
        initial = STRENGTH.get('m'),
        label = 'SKILL LEVEL',
        required = True,
        widget = RadioSelect,
    )
    rival = ChoiceField(
        choices = {},
        widget = HiddenInput,
    )

    # Initializes a ReviewForm for a 'match'.
    # Returns a context dict including 'review_form'
    @classmethod
    def get(klass, rivals):
        form = klass()
        form.fields['rival'].choices = rivals
        form.fields['rival'].initial = next(iter(rivals))
        return {
            'review_form': form,
        }

    @classmethod
    def post(klass, request_post, rivals):
        form = klass(data=request_post)
        form.fields['rival'].choices = rivals
        context = { 'review_form': form }
        if form.is_valid():
            context['conduct'] = form.cleaned_data['conduct'] == 'good'
            context['strength'] = form.cleaned_data['strength']
            context['rival'] = form.cleaned_data['rival']
        return context