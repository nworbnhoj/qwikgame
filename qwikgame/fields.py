import logging
from django.forms import BooleanField, CheckboxSelectMultiple, ChoiceField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect, Select, TypedChoiceField, TypedMultipleChoiceField
from django.utils.translation import gettext_lazy as _
from qwikgame.constants import TWO_DAYS, WEEK_DAYS
from qwikgame.hourbits import Hours24, Hours24x7
from service.locate import Locate
from venue.models import Place
from qwikgame.widgets import ActionMultiple
from qwikgame.widgets import ActionMultiple, DayInputMulti, DayInputRadio, TabInput, TwodayInput, WeekInput, WEEK_ALL

logger = logging.getLogger(__file__)


class DataSelect(RadioSelect):
    data_attr = {}

    def __init__(self, *args, **kwargs):
        self.data_attr = kwargs.pop("data_attr", {})
        super().__init__(*args, **kwargs)

    def create_option(
        self, name, value, label, selected, index, subindex=None, attrs=None
    ):
        option = super().create_option(
            name, value, label, selected, index, subindex=subindex, attrs=attrs
        )
        for key, data in self.data_attr.items():
            if index < len(data):
                option["attrs"]['data-'+key] = data[index]
        return option


class DayMultiField(TypedMultipleChoiceField):
    CHOICES = [(str(hr), str(hr)) for hr in range(24)]
    use_fieldset = False

    def __init__(
        self,
        hours_enable=[*range(24)],
        hours_show=[*range(24)],
        offsetday=None,
        weekday=None,
        *args,
        **kwargs
    ):
        self.widget = DayInputMulti(
            hours_enable=hours_enable,
            hours_show=hours_show,
        )
        if offsetday:
            self.widget.set_data_attr('offsetday', offsetday)
        if weekday:
            self.widget.set_data_attr('weekday', weekday)
        super().__init__(
            choices=DayMultiField.CHOICES,
            coerce=int,
            empty_value=0,
            *args,
            **kwargs)


class DayRadioField(TypedChoiceField):
    CHOICES = [(str(hr), str(hr)) for hr in range(24)]

    def __init__(self, *args, **kwargs):
        self.widget = DayInputRadio()
        super().__init__(choices=DayRadioField.CHOICES, coerce=int, empty_value=0, *args, **kwargs)


class MultipleActionField(MultipleChoiceField):
    widget = ActionMultiple

    def __init__(self, action='delete:', *args, **kwargs):
        self.action = action
        super().__init__(*args, **kwargs)
        self.widget.attrs = {"class": "down left hidden"}
        self.template_name = 'dropdown.html'


class MultiTabField(MultiValueField):
    widget = MultiWidget

    def __init__(self, fields, *args, **kwargs):
        self.field_keys = list(fields.keys())
        super().__init__(fields=fields.values(), *args, **kwargs)

    def compress(self, data_list):
        result = {}
        for i in range(len(self.field_keys)):
            result[self.field_keys[i]] = data_list[i]
        return result


class PlaceField(ChoiceField):
    MAP_CHOICE = ('show-map', _('Select from map'))

    def __init__(self, choices=[], data_attr=None, places=None, *args, **kwargs):
        if not choices:
            choices = [PlaceField.MAP_CHOICE]
        if places:
            choices += [(p.placeid, p.name) for p in places]
            if not data_attr:
                padding = ['' for c in range(len(choices))]
                open = ','.join(map(str, Hours24x7(WEEK_ALL).as_7int()))
                data_attr = {
                    'hours': padding + [open for p in places],
                    'now_weekday': padding + ['' for p in places],
                    'now_hour': padding + ['' for p in places],
                }
        super().__init__(
            choices=choices,
            widget=DataSelect(data_attr=data_attr),
            *args,
            **kwargs)

    def valid_value(self, value):
        if super().valid_value(value):
            return True
        """Check to see if the provided value is a valid Google placeid."""
        if Place.objects.filter(placeid=value, venue__isnull=False).first():
            return True
        if Locate.geodetails(value):
            return True
        return False


class TwodayField(MultiValueField):

    def __init__(self, hours_enable=[*range(24)], hours_show=[*range(24)], *args, **kwargs):
        self.widget = TwodayInput(
            hours_enable=hours_enable, hours_show=hours_show)
        super().__init__(
            fields=(
                [DayMultiField(
                    hours_enable=hours_enable,
                    hours_show=hours_show,
                    label=name,
                    required=False,
                    offsetday=day)
                 for day, name in enumerate(TWO_DAYS)]
            ),
            require_all_fields=False,
            *args,
            **kwargs
        )

    def compress(self, data_list):
        days = []
        for data in data_list:
            hours24 = Hours24()
            if isinstance(data, list):
                for hr in data:
                    hours24.set_hour(hr)
            days.append(hours24)
        return days


class VenueField(PlaceField):

    def __init__(self, venues=[], *args, **kwargs):
        choices = [PlaceField.MAP_CHOICE]
        choices += [(v.placeid, v.name) for v in venues]
        data_attr = {
            'games': [''] + [" ".join(list(v.games.all().values_list('pk', flat=True))) for v in venues],
            'hours': [''] + [v.open_7int_str() for v in venues],
            'now_weekday': [''] + [v.now().isoweekday() % 7 for v in venues],
            'now_hour': [''] + [v.now().hour for v in venues],
            'phone': [''] + [v.phone for v in venues],
            'url': [''] + [v.url for v in venues],
        }
        super().__init__(
            choices=choices,
            data_attr=data_attr,
            *args,
            **kwargs
        )


class WeekField(MultiValueField):

    def __init__(self, hours_enable=[*range(24)], hours_show=[*range(24)], *args, **kwargs):
        self.widget = WeekInput(
            hours_enable=hours_enable, hours_show=hours_show)
        super().__init__(
            fields=(
                [DayMultiField(
                    hours_enable=hours_enable,
                    hours_show=hours_show,
                    label=name,
                    required=False,
                    weekday=day)
                 for day, name in enumerate(WEEK_DAYS)]
            ),
            require_all_fields=False,
            template_name='field.html',
            *args,
            **kwargs
        )

    def compress(self, data_list):
        days = []
        for data in data_list:
            hours24 = Hours24()
            if isinstance(data, list):
                for hr in data:
                    hours24.set_hour(hr)
            days.append(hours24)
        return Hours24x7(days)
