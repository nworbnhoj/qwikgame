import datetime, logging
from django.core.exceptions import ValidationError
from django.forms import BooleanField, CharField, CheckboxInput, CheckboxSelectMultiple, ChoiceField, DecimalField, EmailField, Form, HiddenInput, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect, Textarea, TextInput, TypedChoiceField
from django.utils import timezone
from game.models import Game, Match
from person.models import Person
from player.models import Appeal, Bid, Filter, Friend, Player, Precis
from venue.models import Venue
from qwikgame.constants import STRENGTH
from qwikgame.fields import ActionMultiple, DayField, MultipleActionField, MultiTabField, RadioDataSelect, RangeField, SelectRangeField, TabInput, WeekField
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
    hour = TypedChoiceField(
        coerce=str_to_hours24,
        help_text='When are you keen to play?',
        label='Pick Time to play',
        required=False,
        template_name='field_pillar.html',
        widget=RadioSelect,
    )    

    def clean_hours(self):
        hour = self.cleaned_data["hour"]
        logger.warn(hour)
        if hour.as_bytes() == DAY_NONE:
            raise ValidationError("You must select at least one hour.")
        return hour

    # Initializes an BidForm for an 'appeal'.
    # Returns a context dict including 'rspv_form'
    @classmethod
    def get(klass, appeal):
        hours = appeal.hours24
        form = klass( initial={'hour': hours})
        form.fields['hour'].choices = hours.as_choices()
        form.fields['hour'].sub_text = appeal.date
        form.fields['hour'].widget.attrs = {'class': 'radio_block hour_grid'}
        form.fields['hour'].widget.option_template_name='input_hour.html'
        return {
            'bid_form': form,
        }

    # Initializes an BidForm for an 'appeal'.
    # Returns a context dict including 'bid_form'
    @classmethod
    def post(klass, request_post, appeal):
        form = klass(data=request_post)
        context = {'bid_form': form}
        form.fields['hour'].choices = appeal.hour_choices()
        if form.is_valid():
            context={
                'accept': appeal,
                'hours': form.cleaned_data['hour']
            }
            if 'CANCEL' in request_post:
                cancel = request_post['CANCEL']
                try:
                    context['CANCEL'] = int(cancel)
                except:
                    logger.warn(f'failed to convert CANCEL: {cancel}')
        return context


class BlockedForm(QwikForm):
    blocked = MultipleActionField(
        action='unblock:',
        help_text='When you block a player, neither of you will see the other on qwikgame.',
        label='LIST OF BLOCKED PLAYERS',
        required=False,
    )

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields['blocked'].widget.option_template_name='option_delete.html'
        
    # Initializes a PrivateForm for 'player'.
    # Returns a context dict including 'player_form'
    @classmethod
    def get(klass, player):
        form = klass()
        form.fields['blocked'].choices = klass.blocked_choices(player)
        return {
            'blocked_form': form,
        }

    # Initializes a PrivateForm for 'player'.
    # Returns a context dict including 'player_form'
    @classmethod
    def post(klass, request_post, player):
        context = {}
        user_id = player.user.id
        form = klass(data=request_post)
        form.fields['blocked'].choices = klass.blocked_choices(player)
        if form.is_valid():
            for unblock in form.cleaned_data['blocked']:
                player.blocked.remove(unblock)
            player.save()
        else:
            context = {  
                'blocked_form': form,
            }
        return context

    @classmethod
    def blocked_choices(klass, player):
        choices={}
        for blocked in player.blocked.all():
            choices[blocked.email_hash] = "{} ({})".format(blocked.name(), blocked.facet())
        return choices
    

class FilterForm(QwikForm):
    game = ChoiceField(
        choices = {'ANY':'Any Game'} | Game.choices(),
        help_text='Only see invitations for a particular Game.',
        label = 'Game',
        required=True,
        template_name='dropdown.html',
        widget=RadioSelect(attrs={"class": "down hidden"}),
    )
    place = ChoiceField()    # placeholder for dynamic assignment below
    hours = WeekField(
        help_text='Only see invitations at specific times in your week.',
        label='Time',
        hours=[*range(6,21)],
        required=True,
    )
    lat = DecimalField(
        decimal_places=6,
        max_value=180.0,
        min_value=-180.0,
        required=False,
        widget=HiddenInput(),
    )
    lng = DecimalField(
        decimal_places=6,
        max_value=180.0,
        min_value=-180.0,
        required=False,
        widget=HiddenInput(),
    )
    placeid = CharField(
        required=False,
        widget=HiddenInput(),
    )

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    def clean(self):
        cleaned_data = super().clean()
        if not cleaned_data.get('place'):
            if not cleaned_data.get('placeid'):
                self.add_error(
                    "place",
                    'Sorry, that Venue selection did not work. Please try again.'
                )

    def clean_hours(self):
        hours = self.cleaned_data["hours"]
        if hours.as_bytes() == WEEK_NONE:
            raise ValidationError("You must select at least one hour in the week.")
        return hours

    # Initializes an FilterForm for 'player'.
    # Returns a context dict including 'filter_form'
    @classmethod
    def get(klass, player, game=None, hours=WEEK_NONE, strength=None, place='map'):
        form = klass(
                initial = {
                    'game': game,
                    'hours': hours,
                    # 'strength': strength,
                    'place': place,
                },
            )
        open = Hours24x7(WEEK_ALL).as_7int()
        places = player.place_suggestions(12)[:12]
        logger.warn(places)
        choices = [('ANY','Anywhere'), ('show-map', 'Select from map'), ('placeid', '')]
        choices += [(p.placeid, p.name) for p in places]
        form.fields['place'] = ChoiceField(
            choices = choices,
            help_text='Only see invitations for a particular Venue.',
            label='PLACE',
            template_name='dropdown.html',
            widget=RadioDataSelect(
                attrs={"class": "down hidden"},
                data_attr={
                    'hours': ['','',''] + [p.open_week.as_7int() if p.is_venue else open for p in places],
                    'now_weekday': ['','',''] + [p.now().weekday() if p.is_venue else '' for p in places],
                    'now_hour': ['','',''] + [p.now().hour if p.is_venue else '' for p in places],
                }
            )
        )
        region = player.region_favorite()
        if region:
            form.fields['lat'].initial = region.lat
            form.fields['lng'].initial = region.lng
        return { 'filter_form': form }

    # Processes a FilterForm for 'player'.
    # Returns a context dict game, venue|placeid, hours
    @classmethod
    def post(klass, request_post, player):
        form = klass(data=request_post)
        form.fields['place'].choices += player.place_choices()
        context = { 'filter_form': form }
        if form.is_valid():
            context = {
                'game': form.cleaned_data['game'],
                'hours': form.cleaned_data['hours'],
            }
            placeid = form.cleaned_data.get('place')
            if placeid == 'ALL':
                pass
            elif placeid == 'placeid':
                placeid = form.cleaned_data['placeid']
            context['placeid'] = placeid
        else:
            logger.info(form)
        return context
    

class FiltersForm(QwikForm):
    filters = MultipleChoiceField(
        label='no active filters',
        required=False,
        widget=CheckboxSelectMultiple,
    )

    def __init__(self, player, *args, **kwargs):
        super().__init__(*args, **kwargs)
        filters = Filter.objects.filter(player=player)
        active = [ str(filter.id) for filter in filters.filter(active=True) ]
        choices = { str(filter.id) : filter for filter in filters}
        self.fields['filters'].choices = choices
        self.fields['filters'].label = '{} active filters'.format(len(active))
        self.fields['filters'].initial = active
        # form.fields['filters'].widget.option_template_name = 'django/forms/widgets/checkbox_option.html'

    @classmethod
    def get(klass, player):
        return { 'filters_form' : klass(player), }

    @classmethod
    def post(klass, request_post, player):
        form = klass(player, data=request_post)
        context = {'filters_form': form}
        if form.is_valid():
            if 'ACTIVATE' in request_post:
                context['ACTIVATE'] = form.cleaned_data['filters']
            if 'DELETE' in request_post:
                context['DELETE'] = form.cleaned_data['filters']
        return context


class FriendForm(QwikForm):
    email = EmailField(
        label = "Friend's email address",
        max_length=255,
        required = True,
    )
    name = CharField(
        max_length=32,
        required=False,
    )

    @classmethod
    def get(klass, friend=None):
        form = klass()
        if friend:
            form.fields['email'].initial = friend.email
            form.fields['name'].initial = friend.name
        form.fields['email'].widget.attrs = { 'placeholder': 'Type email address'}
        form.fields['name'].widget.attrs = { 'placeholder': 'A screen name for your friend (optional)'}
        return { 'friend_form' : form, }

    @classmethod
    def post(klass, request_post):
        form = klass(data=request_post)
        context = {'friend_form': form}
        if form.is_valid():
            context={
                'email': form.cleaned_data['email'],
                'name': form.cleaned_data['name']
            }
            if 'DELETE' in request_post:
                delete = request_post['DELETE']
                try:
                    context['DELETE'] = int(delete)
                except:
                    logger.warn(f'failed to convert DELETE: {delete}')
        return context


class StrengthForm(QwikForm):
    game = ChoiceField(
        choices = Game.choices(),
        label = 'GAME',
        required = True,
        template_name='dropdown.html', 
        widget=RadioSelect(attrs={"class": "down hidden"})
    )
    strength = ChoiceField(
        choices = STRENGTH,
        initial = STRENGTH.get('m'),
        label = 'FRIEND SKILL LEVEL',
        required = True,
        widget = RadioSelect,
    )

    @classmethod
    def get(klass, strength=None):
        form = klass()
        if strength:
            form.fields['game'].initial = strength.game.code
            form.fields['strength'].initial = strength.relative
        return { 'strength_form' : form, }

    @classmethod
    def post(klass, request_post):
        form = klass(data=request_post)
        context = {'strength_form': form}
        if form.is_valid():
            context={
                'game': form.cleaned_data['game'],
                'strength': form.cleaned_data['strength']
            }
            if 'DELETE_STRENGTH' in request_post:
                delete = request_post['DELETE_STRENGTH']
                try:
                    context['DELETE_STRENGTH'] = int(delete)
                except:
                    logger.warn(f'failed to convert DELETE_STRENGTH: {delete}')
        return context


class KeenForm(QwikForm):
    game = ChoiceField(
        choices = Game.choices(),
        label = 'GAME',
        required = True,
        template_name='dropdown.html', 
        widget=RadioSelect(attrs={"class": "down hidden"})
    )
    place = ChoiceField()    # placeholder for dynamic assignment below
    today = DayField(
        help_text='When are you keen to play?',
        label='TODAY',
        hours=[*range(6,21)],
        offsetday='0',
        required=False,
        template_name='field.html',
    )
    tomorrow = DayField(
        help_text='When are you keen to play?',
        label='TOMORROW',
        hours=[*range(6,21)],
        offsetday='1',
        required=False,
        template_name='field.html'
    )
    friends = MultipleActionField(
        action='invite:',
        help_text='Invite your friends to play this qwikgame.',
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
        self.fields['friends'].sub_text = 'Add Friends'
        self.fields['friends'].url = 'friends'
        today = timezone.now()
        tomorrow = today + datetime.timedelta(days=1)
        self.fields['today'].sub_text = today.strftime('%A')
        self.fields['today'].help_text = 'What time are you keen to play today?'
        self.fields['tomorrow'].sub_text = tomorrow.strftime('%A')
        self.fields['tomorrow'].help_text = 'What time are you keen to play tomorrow?'
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
                    'now_weekday': ['',''] + [v.now().weekday() for v in venues],
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
    def get(klass, player, game=None, hours=WEEK_NONE, strength=None, venue_id='map'):
        form = klass(
                initial = {
                    'game': game,
                    # 'strength': strength,
                    'today': DAY_NONE,       # TODO extract today from hours
                    'tomorrow': DAY_NONE,    # TODO extract tomorrow from hours
                    'place': venue_id,
                },
            )
        form.personalise(player)
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
                friends = form.cleaned_data['friends']
                context = {
                    'friends': { Player.objects.get(pk=f) for f in friends },
                    'game': form.cleaned_data['game'],
                    'today': form.cleaned_data['today'],
                    'tomorrow': form.cleaned_data['tomorrow'],
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


class PrecisForm(QwikForm):

    def __init__(self, *args, **kwargs):
        precis = kwargs.pop('precis')
        super(PrecisForm, self).__init__(*args, **kwargs)
        fields, widgets, initial = {}, {}, []
        for p in precis:
            name = p.game.code
            widget = Textarea(
                attrs = {
                    'label': p.game.name,
                    'placeholder': "Let rivals know why they want to play you.",
                },
            )
            widgets[p.game.code] = widget
            fields[p.game.code] = CharField(label=p.game.name, required=False)
            initial.append(p.text)
        self.fields['precis'] = MultiTabField(
            fields,
            label='ABOUT',
            require_all_fields=False,
            template_name = 'field.html',
            widget=TabInput(widgets))
        self.fields['precis'].help_text = "Let rivals know why they want to play you."
        self.fields['precis'].initial = initial


    # Initializes a PublicForm with 'request_post' for 'player'.
    # Returns a context dict including 'precis_form' 'precis' & 'reputation'
    @classmethod
    def get(klass, player):
        return {
            'precis_form': PrecisForm(
                precis = Precis.objects.filter(player__user__id=player.user.id)
            ),
            'reputation': player.reputation(),
        }

    # Initializes a PublicForm for 'player'.
    # Returns a context dict including 'precis_form' 'precis' & 'reputation'
    @classmethod
    def post(klass, request_post, player):
        context = {}
        user_id = player.user.id
        precis_form = PrecisForm(
            data=request_post, 
            precis=Precis.objects.filter(player__user__id=player.user.id)
        )
        if precis_form.is_valid():
            for game_code, text in precis_form.cleaned_data['precis'].items():
                precis = Precis.objects.get(game=game_code, player=player)
                precis.text = text
                precis.save()
        else:
            context = {
                'precis_form': precis_form,
                'reputation': player.reputation(),
            }
        return context


class PublicForm(QwikForm):
    pass
