import datetime, logging
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
        label = 'GAME',
        required=True,
        template_name='dropdown.html',
        widget=RadioSelect(attrs={"class": "down hidden"}),
    )
    place = ChoiceField()    # placeholder for dynamic assignment below
    hours = WeekField(
        help_text='Only see invitations at specific times in your week.',
        label='TIME',
        hours_enable=[*range(6,21)],
        hours_show=[*range(6,21)],
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
    def get(klass, player, game=None, hours=WEEK_NONE, strength=None, place='map', places=[]):
        form = klass(
                initial = {
                    'game': game,
                    'hours': hours,
                    # 'strength': strength,
                    'place': place,
                },
            )
        open = ','.join(map(str, Hours24x7(WEEK_ALL).as_7int()))
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
                    'hours': ['','',''] + [p.open_7int_str() if p.is_venue else open for p in places],
                    'now_weekday': ['','',''] + [p.now().isoweekday() % 7 if p.is_venue else '' for p in places],
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
    def post(klass, request_post, place_choices):
        form = klass(data=request_post)
        choices = [('ANY','Anywhere'), ('placeid', '')] + place_choices
        form.fields['place'].choices = choices
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
        label='NO ACTIVE FILTERS',
        required=False,
        widget=CheckboxSelectMultiple,
    )

    def __init__(self, player, *args, **kwargs):
        super().__init__(*args, **kwargs)
        filters = Filter.objects.filter(player=player)
        active = [ str(filter.id) for filter in filters.filter(active=True) ]
        choices = { str(filter.id) : filter for filter in filters}
        self.fields['filters'].choices = choices
        self.fields['filters'].label = '{} ACTIVE FILTERS'.format(len(active))
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
        label = "FRIEND'S EMAIL ADDRESS",
        max_length=255,
        required = True,
        template_name = 'field.html',
    )
    name = CharField(
        label = 'NAME',
        max_length=32,
        required=False,
        template_name = 'field.html',
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
    strength = SelectRangeField(
        choices = Strength.SCALE,
        initial = Strength.SCALE.get('m'),
        label = 'RIVAL SKILL LEVEL',
        required = True,
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
        self.fields['precis'].required = False


    # Initializes a PublicForm with 'request_post' for 'player'.
    # Returns a context dict including 'precis_form' 'precis' & 'reputation'
    @classmethod
    def get(klass, player):
        return {
            'player': player,
            'precis_form': PrecisForm(
                precis = Precis.objects.filter(player__user__id=player.user.id)
            ),
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
                'player': player,
                'precis_form': precis_form,
            }
        return context


class PublicForm(QwikForm):
    pass
