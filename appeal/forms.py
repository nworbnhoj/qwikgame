import datetime
import logging
from django.core.exceptions import ValidationError
from django.forms import BooleanField, CharField, CheckboxInput, CheckboxSelectMultiple, ChoiceField, DecimalField, EmailField, Form, HiddenInput, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect, Textarea, TextInput, TypedChoiceField
from django.utils.translation import gettext_lazy as _
from game.models import Game, Match
from person.models import Person
from player.models import Filter, Friend, Player, Strength
from venue.models import Venue
from qwikgame.constants import SYSTEM_HASH
from qwikgame.fields import ActionMultiple, DataSelect, DayRadioField, MultipleActionField, MultiTabField, TwodayField, VenueField, WeekField
from qwikgame.forms import QwikForm
from qwikgame.hourbits import Hours24, Hours24x7
from qwikgame.log import Entry
from qwikgame.utils import str_to_hours24
from qwikgame.widgets import DAY_ALL, DAY_NONE, WEEK_ALL, WEEK_NONE

logger = logging.getLogger(__file__)


class AcceptForm(QwikForm):

    # Initializes an AcceptForm for a 'bid'.
    # Returns a context dict including 'accept_form'
    @classmethod
    def get(klass):
        form = klass()
        return {
            'accept_form': form,
        }

    # Initializes an Accept for a 'bid'.
    # Returns a context dict including 'accept_form'
    @classmethod
    def post(klass, request_post, player):
        form = klass(data=request_post)
        context = {
            'accept_form': form,
            'refresh_view': True,
        }
        if form.is_valid():
            accept = request_post.get('accept')
            if accept:
                context['accept'] = int(accept)
            decline = request_post.get('decline')
            if decline:
                context['decline'] = int(decline)
            if 'CANCEL' in request_post:
                cancel = request_post['CANCEL']
                try:
                    context['CANCEL'] = int(cancel)
                except:
                    logger.warn(f'failed to convert CANCEL: {cancel}')
        return context


class BidForm(QwikForm):
    hour = DayRadioField(
        help_text=_('When are you keen to play?'),
        label=_('what time works for you?'),
        required=False,
        template_name='field.html',
    )

    def __init__(self, *args, **kwargs):
        appeal = kwargs.pop('appeal')
        super().__init__(*args, **kwargs)
        self.fields['hour'].widget.set_hours_show(appeal.hour_list())
        next_hour = 24
        if appeal.status == 'A':
            venue_now = appeal.venue.now()
            if venue_now.day < appeal.date.day:
                next_hour = 0
            else:
                next_hour = venue_now.hour + 1
        valid_hours = list(range(next_hour, 24))
        self.fields['hour'].widget.set_hours_enable(valid_hours)

    def clean_hour(self):
        hour = self.cleaned_data["hour"]
        if not hour:
            # if there was only a single hour to select, then select it automatically
            options = self.fields['hour'].widget.hours_show
            if len(options) == 1:
                hour = options[0]
        if not hour:
            raise ValidationError("You must select an hour.")
        return hour

    @classmethod
    def post(klass, request_post, appeal):
        if 'CANCEL' in request_post:
            cancel = request_post['CANCEL']
            try:
                return {'CANCEL': int(cancel)}
            except:
                logger.warn(f'failed to convert CANCEL: {cancel}')
        form = klass(appeal=appeal, data=request_post)
        context = {'bid_form': form}
        if form.is_valid():
            context |= {
                'accept': appeal,
                'hour':  Hours24().set_hour(form.cleaned_data['hour']),
            }
        return context


class KeenForm(QwikForm):
    game = ChoiceField(
        label=_('game'),
        required=True,
        template_name='field.html',
        widget=RadioSelect,
    )
    venue = ChoiceField()    # placeholder for dynamic assignment below
    time = ChoiceField()    # placeholder for dynamic assignment below
    friends = MultipleChoiceField()    # placeholder for dynamic assignment below
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
    # strength = SelectRangeField(
    #     choices={'W':_('much-weaker'), 'w':_('weaker'), 'm':_('well-matched'), 's':_('stronger'), 'S':_('much-stronger')},
    #     help_text=_('Restrict this invitation to Rivals of a particular skill level.'),
    #     label = "RIVAL'S SKILL LEVEL",
    #     required=False,
    #     template_name = 'upgrade.html',
    #     disabled=True, # TODO design Model for reckon strength against Venue/Region
    # )

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    def _init_fields(self, player):
        self._init_friends(player.friend_choices())
        self._init_game()
        self._init_venue(player.venue_suggestions(
            20).order_by('name').all()[:20])
        self._init_time()

    def _init_friends(self, choices):
        self.fields['friends'] = MultipleChoiceField(
            choices=choices,
            # help_text=_('Invite your friends to play this qwikgame.'),
            label=_('notify friend by email'),
            required=False,
            template_name='field.html',
            widget=CheckboxSelectMultiple,
        )

    def _init_game(self):
        self.fields['game'].choices += Game.choices()

    def _init_venue(self, venues):
        self.fields['venue'] = VenueField(
            label=_('venue'),
            required=True,
            template_name='field.html',
            venues=venues,
        )

    def _init_regions(self):
        pass

    def _init_time(self):
        self.fields['time'] = TwodayField(
            hours_enable=[*range(6, 22)],
            hours_show=[*range(6, 22)],
            label=_('time'),
            required=True,
            template_name='field.html',
        )

    def _prep_fields(self, player, venue=None):
        self._prep_friends()
        self._prep_game()
        self._prep_venue(player.venue_suggestions(
            20).order_by('name').all()[:20])
        self._prep_time(venue)
        self._prep_regions(player.region_favorite())

    def _prep_friends(self):
        pass

    def _prep_game(self):
        pass

    def _prep_venue(self, venues):
        self.fields['venue'].widget.attrs = {'class': 'prefaced',}
        self.fields['venue'].prompt = _('Check Venue Availability')
        self.fields['venue'].prompt_hash = SYSTEM_HASH
        self.fields['venue'].prompt_info = _('QWIKGAME does not book courts')
        self.fields['venue'].prompt_url = ' '

    def _prep_regions(self, region):
        if region:
            self.fields['lat'].initial = region.lat
            self.fields['lng'].initial = region.lng
            self.fields['east'].initial = region.east
            self.fields['north'].initial = region.north
            self.fields['south'].initial = region.south
            self.fields['west'].initial = region.west

    def _prep_time(self, venue=None):
        self.fields['time'].widget.attrs = {'class': 'prefaced',}
        if venue:
            today = venue.now()
            tomorrow = venue.now() + datetime.timedelta(days=1)
            self.fields['time'].pending = f"{today.strftime('%A')} & {tomorrow.strftime('%A')}"  
            self.fields['time'].widget.set_hours_show(0, venue.open_date(today).as_list())
            self.fields['time'].widget.set_hours_show(1, venue.open_date(tomorrow).as_list())
            self.fields['time'].widget.set_hours_enable(0, [h for h in hours if h > today.hour])
            self.fields['time'].widget.set_hours_enable(1, venue.open_date(tomorrow).as_list())

    @classmethod
    def get(klass, player, game=None, hours=WEEK_NONE, strength=None, venue=None):
        form = klass(
            initial={
                'game': game,
                # 'strength': strength,
                'time': [DAY_NONE, DAY_NONE],    # TODO extract tomorrow from hours
                'venue': venue.placeid if venue else 'map',
            },
        )
        form._init_fields(player)
        form._prep_fields(player, venue)
        return {'keen_form': form}

    @classmethod
    def post(klass, request_post, player):
        form = klass(data=request_post)
        form._init_fields(player)
        context = {'keen_form': form}
        if form.is_valid():
            try:
                context = {
                    'friends': [
                        Friend.objects.get(
                            player=player,
                            rival__hash=f
                        )
                        for f in form.cleaned_data['friends']
                    ],
                    'game': form.cleaned_data['game'],
                    'today': form.cleaned_data['time'][0],
                    'tomorrow': form.cleaned_data['time'][1],
                    'placeid': form.cleaned_data['venue'],
                }
            except:
                logger.exception('failed to parse KeenForm')
        else:
            logger.warn('invalid KeenForm')
            form._prep_fields(
                player,
                Venue.objects.filter(
                    placeid=form.cleaned_data.get('venue')).first()
            )
        return context
