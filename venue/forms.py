import logging
from django.core.exceptions import ValidationError
from django.forms import CharField, CheckboxSelectMultiple, ChoiceField, DecimalField, Form, HiddenInput, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect
from django.utils.translation import gettext_lazy as _
from game.models import Game
from venue.models import Region, Venue
from qwikgame.fields import DataSelect, WeekField
from qwikgame.forms import QwikForm


logger = logging.getLogger(__file__)


class GoogleSearchForm(QwikForm):
    query = CharField(
        # help_text=_('Google Places search'),
        label = _('SEARCH'),
        required = True,
    )
    region = ChoiceField(
        choices = Region.choices(),
        help_text=_('restrict the search to a region'),
        label = _('REGION'),
        required = True,
        template_name='dropdown.html', 
        widget=RadioSelect(attrs={"class": "down left hidden"})
    )
    game = ChoiceField(
        choices = Game.choices(),
        help_text = _('Specify the Game for these Qwikgame Venues'),
        label = _('GAME'),
        required = True,
        template_name='field.html',
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
        help_text=_('Select Google Places to add as Qwikgame Venues'),
        label=_('GOOGLE PLACES'),
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


class VenueAddForm(QwikForm):
    game = ChoiceField(
        choices = [(None, '')],
        label = _('GAME:'),
        required = True,
        template_name='field.html',
        widget = RadioSelect,
    )
    place = CharField(
        label = _('PLACE:'),
        template_name='field.html',
        initial='Select from map',
    )
    lat = DecimalField(
        decimal_places=6,
        initial=-36.449786,
        max_value=90.0,
        min_value=-90.0,
        required=False,
        widget=HiddenInput(),
    )
    lng = DecimalField(
        decimal_places=6,
        initial=146.430037,
        max_value=180.0,
        min_value=-180.0,
        required=False,
        widget=HiddenInput(),
    )
    east = DecimalField(
        decimal_places=6,
        initial=180.0,
        max_value=180.0,
        min_value=-180.0,
        required=False,
        widget=HiddenInput(),
    )
    north = DecimalField(
        decimal_places=6,
        initial=80.0,
        max_value=90.0,
        min_value=-90.0,
        required=False,
        widget=HiddenInput(),
    )
    south = DecimalField(
        decimal_places=6,
        initial=-80,
        max_value=90.0,
        min_value=-90.0,
        required=False,
        widget=HiddenInput(),
    )
    west = DecimalField(
        decimal_places=6,
        initial=-180.0,
        max_value=180.0,
        min_value=-180.0,
        required=False,
        widget=HiddenInput(),
    )
    zoom = IntegerField(
        initial=10,
        max_value=20,
        min_value=0,
        required=False,
        widget=HiddenInput(),
    )
    placeid = CharField(
        required=False,
        widget=HiddenInput(),
    )

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    @classmethod
    def get(klass, player, game=None, venue=None):
        form = klass(initial = {'game': game,},)
        form.fields['game'].choices += Game.choices()
        form.fields['game'].sub_text = ' '
        form.fields['place'].sub_text = ' '
        return { 'venue_add_form': form, }

    @classmethod
    def post(klass, request_post, player):
        context = {}
        form = klass(data=request_post)
        form.fields['game'].choices += Game.choices()
        form.fields['game'].sub_text = ' '
        form.fields['place'].sub_text = ' '
        if form.is_valid():
            try:
                context = {
                    'game': form.cleaned_data['game'],
                    'placeid': form.cleaned_data['placeid'],
                    }
            except:
                logger.exception('failed to parse VenueAddForm')
        else:
            logger.warn('invalid VenueAddForm')
        context |= {'venue_add_form': form}
        return context