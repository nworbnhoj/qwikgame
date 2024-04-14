from django.forms import BooleanField, CheckboxInput, CheckboxSelectMultiple, ChoiceField, Form, HiddenInput, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from game.models import Game
from qwikgame.fields import ActionMultiple, MultipleActionField
from player.models import Available, STRENGTH, WEEK_DAYS
from venue.models import Venue
from qwikgame.forms import QwikForm
from qwikgame.fields import ActionMultiple, RangeField, SelectRangeField, MultipleActionField, WeekField
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
        range=range(6,21),
    )
    strength = SelectRangeField(
        choices={'W':'Very Weak', 'w':'Weak', 'm':'Average', 's':'Strong', 'S':'Very Strong'},
        help_text='This helps qwikgame to match you skill with other players',
        label = 'MY SKILL LEVEL AT THIS VENUE',
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
    def get(klass, player, game=None, hide=[], hours=None, strength=None, venue=None):
        return {
            'available_form': klass(
                hide=hide,
                initial = {
                    'game': game,
                    'hours': hours,
                    'strength': strength,
                    'venues': 'map'
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
            Available.objects.create(
                game=get_object_or_404(Game, pk=form.cleaned_data['game']),
                player=player,
                venue=get_object_or_404(Venue, pk=form.cleaned_data['venue']),
                hours=form.cleaned_data['hours'],
            )
            # opinion = opinion.objects.create()
            # Strength.objects.create()
        else:
            context = {'available_form': form}
        return context