import datetime, logging
from authenticate.forms import RegisterForm
from django.contrib.auth.tokens import default_token_generator
from django.core.exceptions import ValidationError
from django.forms import BooleanField, CharField, CheckboxInput, CheckboxSelectMultiple, ChoiceField, DecimalField, EmailField, Form, HiddenInput, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect, Textarea, TextInput, TypedChoiceField
from game.models import Game, Match
from person.models import Person
from player.models import Filter, Friend, Player, Precis, Strength
from venue.models import Venue
from qwikgame.fields import ActionMultiple, DayRadioField, DayMultiField, MultipleActionField, MultiTabField, RadioDataSelect, RangeField, SelectRangeField, TabInput, WeekField
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
        help_text='When are you keen to play?',
        label='PICK TIME TO PLAY',
        required=True,
        template_name='field.html',
    )

    def clean_hour(self):
        hour = self.cleaned_data["hour"]
        if not hour:
            raise ValidationError("You must select at least one hour.")
        return hour

    # Initializes an BidForm for an 'appeal'.
    # Returns a context dict including 'rspv_form'
    @classmethod
    def get(klass, appeal):
        form = klass()
        form.fields['hour'].widget.set_hours_show(appeal.hour_list())
        next_hour = 24
        if appeal.status == 'A':
            venue_now = appeal.venue.now()
            if venue_now.day < appeal.date.day:
                next_hour = 0
            else:
                next_hour = venue_now.hour + 1
        valid_hours = list(range(next_hour, 24))
        form.fields['hour'].widget.set_hours_enable(valid_hours)
        return { 'bid_form': form }

    # Initializes an BidForm for an 'appeal'.
    # Returns a context dict including 'bid_form'
    @classmethod
    def post(klass, request_post, appeal):
        if 'CANCEL' in request_post:
            logger.warn('here')
            cancel = request_post['CANCEL']
            try:
                return {'CANCEL' : int(cancel) }
            except:
                logger.warn(f'failed to convert CANCEL: {cancel}')
        form = klass(data=request_post)
        context = { 'bid_form': form }
        if form.is_valid():
            context |= {
                'accept': appeal,
                'hour':  Hours24().set_hour(form.cleaned_data['hour']),
            }
        return context


class InviteForm(RegisterForm):

    def __init__(self, to_email, email_context, *args, **kwargs):
        data={ 'email': to_email }
        super().__init__(data, *args, **kwargs)
        self.email_context=email_context

    def save(self, request):
        super().save(
            domain_override=None,
            subject_template_name='appeal/invite_email_subject.txt',
            email_template_name='appeal/invite_email_text.html',
            use_https=False,
            token_generator=default_token_generator,
            from_email='accounts@qwikgame.org',
            request=request,
            html_email_template_name='appeal/invite_email_html.html',
            extra_email_context=self.email_context,
        )


class KeenForm(QwikForm):
    game = ChoiceField(
        choices = Game.choices(),
        label = 'GAME',
        required = True,
        template_name='dropdown.html', 
        widget=RadioSelect(attrs={"class": "down hidden"})
    )
    place = ChoiceField()    # placeholder for dynamic assignment below
    today = DayMultiField(
        help_text='When are you keen to play?',
        hours_enable=[*range(6,22)],
        hours_show=[*range(6,22)],
        label='TODAY',
        offsetday='0',
        required=False,
        template_name='field.html',
    )
    tomorrow = DayMultiField(
        help_text='When are you keen to play?',
        hours_enable=[*range(6,22)],
        hours_show=[*range(6,22)],
        label='TOMORROW',
        offsetday='1',
        required=False,
        template_name='field.html',
    )
    friends = MultipleActionField(
        action='invite:',
        # help_text='Invite your friends to play this qwikgame.',
        label='FRIENDS',
        required=False,
    )
    lat = DecimalField(
        decimal_places=6,
        initial=-36.449786,
        max_value=180.0,
        min_value=-180.0,
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
    placeid = CharField(
        required=False,
        widget=HiddenInput(),
    )
    # strength = SelectRangeField(
    #     choices={'W':'Much Weaker', 'w':'Weaker', 'm':'Well Matched', 's':'Stronger', 'S':'Much Stronger'},
    #     help_text='Restrict this invitation to Rivals of a particular skill level.',
    #     label = "RIVAL'S SKILL LEVEL",
    #     required=False,
    #     template_name = 'upgrade.html',
    #     disabled=True, # TODO design Model for reckon strength against Venue/Region
    # )

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    def clean(self):
        cleaned_data = super().clean()
        if not cleaned_data.get('today'):
            if not cleaned_data.get('tomorrow'):
                msg = 'Please select at least one hour in today or tomorrow.'
                self.add_error('today', msg)
                self.add_error('tomorrow', msg)
        if not cleaned_data.get('place'):
            if not cleaned_data.get('placeid'):
                self.add_error(
                    "place",
                    'Sorry, that Venue selection did not work. Please try again.'
                )
        if 'reveal_friends' in self.data:
            if len(cleaned_data['friends']) == 0:
                self.add_error(
                    'friends',
                    'Please invite at least one Friend.'
                )

    def clean_place(self):
        place_id = self.cleaned_data.get('place')
        if place_id == 'placeid':
            return place_id
        if Venue.objects.filter(placeid=place_id).exists():
            return place_id
        raise ValidationError(
            'Sorry, that Venue selection did not work. Please try again.'
        )

    def personalise(self, player):
        self.fields['friends'].choices = player.friend_choices()
        self.fields['friends'].reveal = 'Invite Friends Only?'
        if not self.fields['friends'].choices:
            self.fields['friends'].sub_text = "You don't have any added friends yet. Please add them from the Friends tab"
        self.fields['today'].sub_text = ' '
        self.fields['tomorrow'].sub_text = ' '
        venues = player.venue_suggestions(12).order_by('name').all()[:12]
        choices = [('show-map', 'Select from map'), ('placeid', '')]
        choices += [(v.placeid, v.name) for v in venues]
        self.fields['place'] = ChoiceField(
            choices = choices,
            label='VENUE',
            required = True,
            template_name='dropdown.html', 
            widget=RadioDataSelect(
                attrs={"class": "down hidden"},
                data_attr={
                    'hours': ['',''] + [v.open_7int_str() for v in venues],
                    'now_weekday': ['',''] + [v.now().isoweekday() % 7 for v in venues],
                    'now_hour': ['',''] + [v.now().hour for v in venues],
                }
            )
        )
        region = player.region_favorite()
        if region:
            self.fields['lat'].initial = region.lat
            self.fields['lng'].initial = region.lng

    # Initializes an KeenForm for 'player'.
    # Returns a context dict including 'keen_form'
    @classmethod
    def get(klass, player, game=None, hours=WEEK_NONE, strength=None, venue=None):
        form = klass(
                initial = {
                    'game': game,
                    # 'strength': strength,
                    'today': DAY_NONE,       # TODO extract today from hours
                    'tomorrow': DAY_NONE,    # TODO extract tomorrow from hours
                    'place': venue.placeid if venue else 'map',
                },
            )
        form.personalise(player)
        if venue:
            today = venue.now()
            logger.warn(today)
            tomorrow = today + datetime.timedelta(days=1)
            form.fields['today'].sub_text = today.strftime('%A')
            form.fields['tomorrow'].sub_text = tomorrow.strftime('%A')
        return {
            'keen_form': form,
        }

    # Initializes an KeenForm for 'player'.
    # Returns a context dict including 'keen_form'
    @classmethod
    def post(klass, request_post, player):
        context = {}
        form = klass(data=request_post)
        form.personalise(player)
        if form.is_valid():
            try:
                today = Hours24()
                tomorrow = Hours24()
                if isinstance(form.cleaned_data['today'], list):
                    for hr in form.cleaned_data['today']:
                        today.set_hour(hr)
                if isinstance(form.cleaned_data['tomorrow'], list):
                    for hr in form.cleaned_data['tomorrow']:
                        tomorrow.set_hour(hr)
                friends = []
                if 'reveal_friends' in request_post:
                    form.fields['friends'].reveal_checked = 'checked'
                    friends = form.cleaned_data['friends']
                context = {
                    'friends': [ Friend.objects.get(rival__email_hash=f) for f in friends ],
                    'game': form.cleaned_data['game'],
                    'today': today,
                    'tomorrow': tomorrow,
                    'player_id': form.cleaned_data['placeid'],
                    }
                place_id = form.cleaned_data.get('place')
                if place_id == 'placeid':
                    context['placeid'] = form.cleaned_data['placeid']
                else:
                    context['placeid'] = form.cleaned_data['place']
            except:
                logger.exception('failed to parse KeenForm')
        else:
            logger.warn('invalid KeenForm')
        context |= {'keen_form': form}
        return context