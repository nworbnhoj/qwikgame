import datetime, logging
from django.core.exceptions import ValidationError
from django.forms import BooleanField, CharField, CheckboxInput, CheckboxSelectMultiple, ChoiceField, DecimalField, Form, HiddenInput, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect, Textarea, TextInput, TypedChoiceField
from django.utils import timezone
from game.models import Game, Match
from person.models import Person
from player.models import Appeal, Filter, Friend, Invite, Player, Precis
from venue.models import Venue
from qwikgame.fields import ActionMultiple, DayField, MultipleActionField, MultiTabField, RangeField, SelectRangeField, TabInput, WeekField
from qwikgame.forms import QwikForm
from qwikgame.log import Entry
from qwikgame.utils import str_to_hours24
from qwikgame.widgets import DAY_ALL, DAY_NONE, WEEK_ALL, WEEK_NONE

logger = logging.getLogger(__file__)


class AcceptForm(QwikForm):

    # Initializes an AcceptForm for an 'invite'.
    # Returns a context dict including 'accept_form'
    @classmethod
    def get(klass):
        form = klass()
        return {
            'accept_form': form,
        }

    # Initializes an Accept for an 'invite'.
    # Returns a context dict including 'accept_form'
    @classmethod
    def post(klass, request_post, player):
        context={'refresh_view': True}
        form = klass(data=request_post)
        if form.is_valid():
            try:
                if 'accept' in request_post:
                    accept_id = int(request_post['accept'])
                    invite = Invite.objects.get(pk=accept_id)
                    invite.log_event('accept')
                    match = Match (accept=invite)
                    match.save()
                    match.competitors.add(invite.appeal.player, invite.rival)
                    # TODO optimise with https://stackoverflow.com/questions/6996176/how-to-create-an-object-for-a-django-model-with-a-many-to-many-field
                    match.log_event('scheduled', player)
                    invite.delete()
                    context={}
                elif 'decline' in request_post:
                    decline_id = int(request_post['decline'])
                    invite = Invite.objects.get(pk=decline_id)
                    invite.delete()
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
        label = 'Game',
        required=True,
        template_name='dropdown.html',
        widget=RadioSelect(attrs={"class": "down hidden"}),
    )
    venue = ChoiceField(
        choices = {'ANY':'Any Venue'} | {'show-map': 'Select from map', 'placeid': ''},
        label='Venue',
        template_name='dropdown.html',
        widget=RadioSelect(attrs={"class": "down hidden"})
    )
    hours = WeekField(
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
        # TODO set distinct for venues
        # TODO include venues for past matches
        filters = Filter.objects.filter(player=player).all()
        venues = [(f.venue_id, f.venue.name) for f in filters]
        form.fields['venue'].choices += venues[:8]
        return { 'filter_form': form }

    # Processes a FilterForm for 'player'.
    # Returns a context dict game, venue|placeid, hours
    @classmethod
    def post(klass, request_post):
        context = {}
        form = klass(data=request_post)
        if form.is_valid():
            game_id = form.cleaned_data['game']
            context['game'] = Game.objects.filter(pk=game_id).first()
            venue_id = form.cleaned_data['venue']
            if venue_id == 'ALL':
                context['venue'] = None
            elif venue_id == 'placeid':
                placeid = form.cleaned_data['placeid']
                context['venue'] = Venue.objects.filter(placeid=placeid).first()
                context['placeid'] = placeid
            elif venue_id.isdigit():
                venue_id = int(venue_id)
                context['venue'] = Venue.objects.filter(pk=venue_id).first()
            else:
                context['venue'] = None
            context['hours'] = form.cleaned_data['hours']
        else:
            logger.info(form)
        context['filter_form'] = form
        return context


class KeenForm(QwikForm):
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
    strength = SelectRangeField(
        choices={'W':'Much Weaker', 'w':'Weaker', 'm':'Well Matched', 's':'Stronger', 'S':'Much Stronger'},
        help_text='Restrict this invitation to Rivals of a particular skill level.',
        label = "RIVAL'S SKILL LEVEL",
        required=False,
        template_name = 'upgrade.html',
        disabled=True, # TODO design Model for reckon strength against Venue/Region
    )

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    def clean(self):
        cleaned_data = super().clean()
        if cleaned_data.get('today').is_none():
            if cleaned_data.get('tomorrow').is_none():
                raise ValidationError(
                    'Please select at least one hour in today or tomorrow.'
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
                now=timezone.now()
                one_day=datetime.timedelta(days=1)
                friends = {Player.objects.get(pk=friend) for friend in form.cleaned_data['friends']}
                game=Game.objects.get(pk=form.cleaned_data['game'])
                venue=Venue.objects.get(pk=form.cleaned_data['venue'])
                # create/update/delete today appeal
                appeal = Appeal.objects.get_or_create(
                    date=now.date(),
                    game=game,
                    player=player,
                    venue=venue,
                )[0]
                if form.cleaned_data['today'].is_none():
                    appeal.delete()
                elif appeal.hours24x7() != form.cleaned_data['today']:
                    appeal.set_hours(form.cleaned_data['today'])
                    appeal.log_event('keen')
                    appeal.log_event('appeal')
                    appeal.save()
                    appeal.invite_rivals(friends)
                # create/update/delete tomorrow appeal
                appeal = Appeal.objects.get_or_create(
                    date=(now + one_day).date(),
                    game=game,
                    player=player,
                    venue=venue,
                )[0]
                if form.cleaned_data['tomorrow'].is_none():
                    appeal.delete()
                elif appeal.hours24x7() != form.cleaned_data['tomorrow']:
                    appeal.set_hours(form.cleaned_data['tomorrow'])
                    appeal.log_event('keen')
                    appeal.log_event('appeal')
                    appeal.save()
                    appeal.invite_rivals(friends)
            except:
                context = {'keen_form': form}
        else:
            context = {'keen_form': form}
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




class RsvpForm(QwikForm):
    hour = TypedChoiceField(
        coerce=str_to_hours24,
        help_text='When are you keen to play?',
        label='Pick Time to play',
        required=False,
        template_name='field_pillar.html',
        widget=RadioSelect,
    )

    # Initializes an RspvForm for an 'invite'.
    # Returns a context dict including 'rspv_form'
    @classmethod
    def get(klass, invite):
        form = klass( initial={'hour': Hours24(invite.hours).as_int()})
        form.fields['hour'].choices = invite.hour_choices()
        form.fields['hour'].sub_text = invite.appeal.date
        form.fields['hour'].widget.attrs = {'class': 'radio_block hour_grid'}
        form.fields['hour'].widget.option_template_name='input_hour.html'
        return {
            'bid_form': form,
        }

    # Initializes an Rsvp for an 'invite'.
    # Returns a context dict including 'bid_form'
    @classmethod
    def post(klass, request_post, invite):
        context = {}
        form = klass(data=request_post)
        form.fields['hour'].choices = invite.hour_choices()
        if form.is_valid():
            try:
                if 'accept' in request_post:
                    invite.hours = form.cleaned_data['hour']
                    invite.save()
                    invite.log_event('bid')
                elif 'decline' in request_post:
                    invite.delete()
            except:
                context = {'bid_form': form}
        else:
            context = {'bid_form': form}
        return context


class ScreenForm(QwikForm):
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
