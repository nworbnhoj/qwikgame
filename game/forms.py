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


class ActiveForm(QwikForm):
    games = MultipleChoiceField(
        choices = Game.choices(),
        label=None,
        required=False,
        template_name='field_naked.html',
        widget=IconSelectMultiple(attrs = {'class':'post'}, icons=Game.icons())
    )
        
    # Initializes a GameForm for 'player'.
    # Returns a context dict including 'game_form'
    @classmethod
    def get(klass, player):
        return {
            'game_form': klass(
                initial = {'games': [game.code for game in player.games.all()]}
            )
        }

    # Initializes a PrivateForm for 'player'.
    # Returns a context dict including 'player_form'
    @classmethod
    def post(klass, request_post, player):
        context = {}
        form = klass(data=request_post)
        if form.is_valid():
            player.games.clear()
            for game_code in form.cleaned_data['games']:
                game = Game.objects.get(code=game_code)
                player.games.add(game)
            player.save()
        else:
            context = {'game_form': form}
        return context


class AvailableForm(QwikForm):
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
    hours = WeekField(
        help_text='When are you usually available to play at this Venue?',
        label='MY AVAILABILITY AT THIS VENUE',
        hours=[*range(6,21)],
        required=True,
    )
    strength = SelectRangeField(
        choices={'W':'Very Weak', 'w':'Weak', 'm':'Average', 's':'Strong', 'S':'Very Strong'},
        help_text='This helps qwikgame to match you skill with other players',
        label = 'MY SKILL LEVEL AT THIS VENUE',
        required=False,
        disabled=True, # TODO design Model for reckon strength against Venue/Region
    )

    def __init__(self, hide=[], *args, **kwargs):
        super().__init__(*args, **kwargs)
        for field in hide:
            if field in self.fields:
                self.fields[field].widget = HiddenInput()


    # Initializes an AddVenueForm for 'player'.
    # Returns a context dict including 'add_venue_form'
    @classmethod
    def get(klass, player, game=None, hide=[], hours=None, strength=None, venue='map'):
        return {
            'available_form': klass(
                hide=hide,
                initial = {
                    'game': game,
                    'hours': hours,
                    'strength': strength,
                    'venue': venue,
                },
            )
        }

    # Initializes an AddVenueForm for 'player'.
    # Returns a context dict including 'add_venue_form'
    @classmethod
    def post(klass, request_post, player, hide=[]):
        context = {}
        form = klass(data=request_post)
        if form.is_valid():
            try:
                game=Game.objects.get(pk=form.cleaned_data['game']),
                venue=Venue.objects.get(pk=form.cleaned_data['venue']),
                available = Available.objects.get_or_create(player=player, game=game[0], venue=venue[0])
                available[0].hours = form.cleaned_data['hours']
                available[0].save()
            except:
                context = {'available_form': form}
            # opinion = opinion.objects.create()
            # Strength.objects.create()
        else:
            context = {'available_form': form}
        return context


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
    def post(klass, request_post, match, player):
        form = klass(data=request_post)
        if form.is_valid():
            try:
                entry = Entry(
                    icon = player.user.person.icon,
                    id = player.facet(),
                    name = player.user.person.name,
                    text = form.cleaned_data['txt']
                )
                match.log_entry(entry)
            except:
                pass # simply drop the input
        else:
            pass # simply drop the input
        return {} # never represent the form