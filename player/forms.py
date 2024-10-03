import datetime, logging
from django.core.exceptions import ValidationError
from django.forms import BooleanField, CharField, CheckboxInput, CheckboxSelectMultiple, ChoiceField, DecimalField, Form, HiddenInput, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect, Textarea, TextInput, TypedChoiceField
from django.utils import timezone
from game.models import Game, Match
from person.models import Person
from player.models import Appeal, Bid, Filter, Friend, Player, Precis
from venue.models import Venue
from qwikgame.fields import ActionMultiple, DayField, MultipleActionField, MultiTabField, RangeField, SelectRangeField, TabInput, WeekField
from qwikgame.forms import QwikForm
from qwikgame.hourbits import Hours24
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
        context={'refresh_view': True}
        form = klass(data=request_post)
        if form.is_valid():
            try:
                if 'accept' in request_post:
                    accept_id = int(request_post['accept'])
                    bid = Bid.objects.get(pk=accept_id)
                    bid.log_event('accept')
                    match = Match (accept=bid)
                    match.save()
                    match.competitors.add(bid.appeal.player, bid.rival)
                    # TODO optimise with https://stackoverflow.com/questions/6996176/how-to-create-an-object-for-a-django-model-with-a-many-to-many-field
                    match.log_event('scheduled', player)
                    bid.delete()
                    context={}
                elif 'decline' in request_post:
                    decline_id = int(request_post['decline'])
                    bid = Bid.objects.get(pk=decline_id)
                    bid.delete()
            except:
                # TODO log exception
                pass
        else:
            pass
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
    venue = ChoiceField(
        choices = {'ANY':'Any Venue'} | {'show-map': 'Select from map', 'placeid': ''},
        help_text='Only see invitations for a particular Venue.',
        label='Venue',
        template_name='dropdown.html',
        widget=RadioSelect(attrs={"class": "down hidden"})
    )
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
        if not cleaned_data.get('venue'):
            if not cleaned_data.get('placeid'):
                self.add_error(
                    "venue",
                    'Sorry, that Venue selection did not work. Please try again.'
                )

    def clean_hours(self):
        hours = self.cleaned_data["hours"]
        if hours.as_bytes() == WEEK_NONE:
            raise ValidationError("You must select at least one hour in the week.")
        return hours

    def clean_venue(self):
        venue_id = self.cleaned_data.get('venue')
        if venue_id == 'placeid':
            return venue_id
        if venue_id.isdigit():
            if Venue.objects.filter(pk=int(venue_id)).exists():
                return venue_id
        raise ValidationError(
            'Sorry, that Venue selection did not work. Please try again.'
        )

    # Initializes an FilterForm for 'player'.
    # Returns a context dict including 'filter_form'
    @classmethod
    def get(klass, player, game=None, hours=WEEK_NONE, strength=None, venue='map'):
        form = klass(
                initial = {
                    'game': game,
                    'hours': hours,
                    # 'strength': strength,
                    'venue': venue,
                },
            )
        form.fields['venue'].choices += player.venue_choices()[:8]
        return { 'filter_form': form }

    # Processes a FilterForm for 'player'.
    # Returns a context dict game, venue|placeid, hours
    @classmethod
    def post(klass, request_post, player):
        form = klass(data=request_post)
        form.fields['venue'].choices += player.venue_choices()
        context = { 'filter_form': form }
        if form.is_valid():
            game_id = form.cleaned_data['game']
            context = {
                'game': Game.objects.filter(pk=game_id).first(),
                'hours': form.cleaned_data['hours'],
            }
            venue_id = form.cleaned_data.get('venue')
            if venue_id == 'ALL':
                pass
            if venue_id == 'placeid':
                placeid = form.cleaned_data['placeid']
                venue = Venue.objects.filter(placeid=placeid)
                if venue.exists():
                    context['venue'] = venue.first()
                else:
                    context['placeid'] = placeid
            else:
                context['venue'] = Venue.objects.filter(pk=venue_id).first()
        else:
            logger.info(form)
        return context


class KeenForm(QwikForm):
    game = ChoiceField(
        choices = Game.choices(),
        label = 'GAME',
        required = True,
        template_name='dropdown.html', 
        widget=RadioSelect(attrs={"class": "down hidden"})
    )
    venue = ChoiceField(
        choices = {'show-map': 'Select from map', 'placeid': ''},
        label='VENUE',
        required = True,
        template_name='dropdown.html', 
        widget=RadioSelect(attrs={"class": "down hidden"})
    )
    today = DayField(
        help_text='When are you keen to play?',
        label='TODAY',
        hours=[*range(6,21)],
        required=False,
        template_name='field.html'
    )
    tomorrow = DayField(
        help_text='When are you keen to play?',
        label='TOMORROW',
        hours=[*range(6,21)],
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
        if not cleaned_data.get('venue'):
            if not cleaned_data.get('placeid'):
                self.add_error(
                    "venue",
                    'Sorry, that Venue selection did not work. Please try again.'
                )

    def clean_venue(self):
        venue_id = self.cleaned_data.get('venue')
        if venue_id == 'placeid':
            return venue_id
        if venue_id.isdigit():
            if Venue.objects.filter(pk=int(venue_id)).exists():
                return venue_id
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
        self.fields['venue'].choices += player.venue_choices()[:8]


    # Initializes an KeenForm for 'player'.
    # Returns a context dict including 'keen_form'
    @classmethod
    def get(klass, player, game=None, hours=WEEK_NONE, strength=None, venue='map'):
        form = klass(
                initial = {
                    'game': game,
                    'strength': strength,
                    'today': DAY_ALL,
                    'tomorrow': DAY_ALL,
                    'venue': venue,
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
                    'game': Game.objects.get(pk=form.cleaned_data['game']),
                    'today': form.cleaned_data['today'],
                    'tomorrow': form.cleaned_data['tomorrow'],
                    'player_id': form.cleaned_data['placeid'],
                    }
                venue_id = form.cleaned_data.get('venue')
                if venue_id == 'placeid':
                    placeid = form.cleaned_data['placeid']
                    venue = Venue.objects.filter(placeid=placeid)
                    if venue.exists():
                        context['venue'] = venue.first()
                    else:
                        context['placeid'] = placeid
                else:
                    context['venue'] = Venue.objects.filter(pk=venue_id).first()
            except:
                logger.exception('failed to parse KeenForm')
        else:
            logger.warn('invalid KeenForm')
        context |= {'keen_form': form}
        return context


class PublicForm(QwikForm):
    pass


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




class BidForm(QwikForm):
    hour = TypedChoiceField(
        coerce=str_to_hours24,
        help_text='When are you keen to play?',
        label='Pick Time to play',
        required=False,
        template_name='field_pillar.html',
        widget=RadioSelect,
    )

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
        context = {}
        form = klass(data=request_post)
        form.fields['hour'].choices = appeal.hour_choices()
        if form.is_valid():
            try:
                if 'accept' in request_post:
                    context={
                        'accept': appeal,
                        'hours': form.cleaned_data['hour']
                    }
            except:
                context = {'bid_form': form}
        else:
            context = {'bid_form': form}
        return context


class FiltersForm(QwikForm):
    filters = MultipleChoiceField(
        label='no active filters',
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

    @classmethod
    def get(klass, player):
        return { 'screen_form' : klass(player), }

    @classmethod
    def post(klass, request_post, player):
        form = klass(player, data=request_post)
        if form.is_valid():
            if 'ACTIVATE' in request_post:
                logger.info('activating filters {}'.format(form.cleaned_data['filters']))
                for filter in Filter.objects.filter(player=player):
                    try:
                        filter.active = str(filter.id) in form.cleaned_data['filters']
                        filter.save()
                    except:
                        logger.exception('failed to activate filter: {} : {}'.format(player, filter.id))
            if 'DELETE' in request_post:
                for filter_code in form.cleaned_data['filters']:
                    try:
                        junk = Filter.objects.get(pk=filter_code)
                        logger.info('Deleting filter: {}'.format(junk))
                        junk.delete()
                    except:
                        logger.exception('failed to delete filter: {} : {}'.format(player, filter_code))
        return {'screen_form': form}
