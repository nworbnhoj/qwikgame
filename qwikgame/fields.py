import logging
from django.forms import BooleanField, CheckboxSelectMultiple, ChoiceField, MultipleChoiceField, MultiValueField, RadioSelect, Select, TypedChoiceField, TypedMultipleChoiceField
from qwikgame.constants import WEEK_DAYS
from qwikgame.hourbits import Hours24, Hours24x7
from qwikgame.widgets import ActionMultiple, MultiWidget
from qwikgame.widgets import ActionMultiple, DayInputMulti, DayInputRadio, RangeInput, SelectRangeInput, TabInput, WeekInput

logger = logging.getLogger(__file__)


class DataSelect(Select):
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
    CHOICES = [(str(hr),str(hr)) for hr in range(24)]
    use_fieldset = False

    def __init__(
            self,
            hours_enable=[*range(24)],
            hours_show=[*range(24)],
            offsetday=None,
            weekday=None,
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
            choices = DayMultiField.CHOICES,
            coerce=int,
            empty_value=0,
            **kwargs)


class DayRadioField(TypedChoiceField):
    CHOICES = [(str(hr),str(hr)) for hr in range(24)]

    def __init__(self, **kwargs):
        self.widget = DayInputRadio()
        super().__init__(choices = DayRadioField.CHOICES, coerce=int, empty_value=0, **kwargs)


class MultipleActionField(MultipleChoiceField):
    widget = ActionMultiple

    def __init__(self, action='delete:', *args, **kwargs):
        self.action = action
        super().__init__(*args, **kwargs)
        self.widget.attrs={"class": "down left hidden"}
        self.template_name='dropdown.html'


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


class RangeField(ChoiceField):
    template_name='field.html'
    widget=RangeInput(attrs={'type': 'range'})

    def __init__(self, max_value=None, min_value=None, step_size=None, **kwargs):
        self.max_value, self.min_value, self.step_size = max_value, min_value, step_size
        super().__init__(
            **kwargs
        )
        self.widget.attrs.update({'max':self.max_value, 'min':self.min_value})


class SelectRangeField(RangeField):
    widget=SelectRangeInput

    def __init__(self, template_name='field.html',**kwargs):
        super().__init__(
            max_value=len(kwargs['choices'])-1,
            min_value=0,
            template_name = template_name,
            **kwargs
        )

    def to_python(self, value):
        try:
            return list(self.choices)[int(value)][0]
        except:
            return None


class WeekField(MultiValueField):

    def __init__(self, hours_enable=[*range(24)], hours_show=[*range(24)], **kwargs):
        self.widget=WeekInput( hours_enable=hours_enable, hours_show=hours_show)
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
