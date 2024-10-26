import logging
from django.core.exceptions import ValidationError
from django.forms import CharField, CheckboxSelectMultiple, ChoiceField, Form, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect
from game.models import Game
from venue.models import Region, Venue
from qwikgame.fields import WeekField
from qwikgame.forms import QwikForm


logger = logging.getLogger(__file__)


class GoogleSearchForm(QwikForm):
    query = CharField(
        # help_text='Google Places search',
        label = 'SEARCH',
        required = True,
    )
    region = ChoiceField(
        choices = Region.choices(),
        help_text='restrict the search to a region',
        label = 'REGION',
        required = True,
        template_name='dropdown.html', 
        widget=RadioSelect(attrs={"class": "down hidden"})
    )
    game = ChoiceField(
        choices = Game.choices(),
        help_text = 'Specify the Game for these Qwikgame Venues',
        label = 'GAME',
        required = True,
        template_name='dropdown.html', 
        widget=RadioSelect(attrs={"class": "down hidden"})
    )

    @classmethod
    def get(klass, game=None, query=None, region=None):
        form = klass()
        form.fields['game'].initial=game
        form.fields['query'].initial=query
        form.fields['region'].initial=region
        return { 'search_form': form, }

    @classmethod
    def post(klass, request_post):
        form = klass(data=request_post)
        context = {'search_form': form}
        if form.is_valid():
            context |= {
                'game': form.cleaned_data['game'],
                'query': form.cleaned_data['query'],
                'region': form.cleaned_data['region'],
            }
        return context


class GooglePlacesForm(QwikForm):
    places = MultipleChoiceField(
        choices = {},
        help_text='Select Google Places to add as Qwikgame Venues',
        label='GOOGLE PLACES',
        required=True,
        widget=CheckboxSelectMultiple,
    )

    @classmethod
    def get(klass, game, places=[]):
        form = klass()
        form.fields['places'].choices = places
        return {
            'game': game,
            'places_form': form,
        }

    @classmethod
    def post(klass, request_post, game, places=[]):
        form = klass(data=request_post)
        form.fields['places'].choices = places
        context = { 'places_form': form }
        if form.is_valid():
            context |= {
                'places': form.cleaned_data['places'],
            }
        return context

