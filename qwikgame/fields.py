import logging
from django.forms import BooleanField, CheckboxSelectMultiple, ChoiceField, MultipleChoiceField, MultiValueField, RadioSelect
from qwikgame.constants import STRENGTH, WEEK_DAYS
from qwikgame.hourbits import Hours24, Hours24x7
from qwikgame.widgets import ActionMultiple, MultiWidget
from qwikgame.widgets import ActionMultiple, DayInput, RangeInput, SelectRangeInput, TabInput, WeekInput

logger = logging.getLogger(__file__)

class RadioDataSelect(RadioSelect):
    def __init__(self, *args, **kwargs):
        self.data_attr = kwargs.pop("data_attr", [])
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


class DayField(MultiValueField):

    def __init__(self, hours=[*range(24)], offsetday=None, weekday=None, **kwargs):
        self.hours = hours
        self.weekday = weekday
        self.widget=DayInput(hours=hours, offsetday=offsetday, weekday=weekday)
        super().__init__(
            error_messages={
                'incomplete': 'incomplete',
                'invalid': 'required',
                'required': 'required',
            },
            fields=(
                [BooleanField(label=hr, required=False)
                for hr in range(24)]
            ),
            require_all_fields=False,
            **kwargs
        )

    def compress(self, data_list):
        bools=[False] * 24
        for hr in self.hours:
            bools[hr] = data_list[hr]
        return Hours24(bools)


class MultipleActionField(MultipleChoiceField):
    widget = ActionMultiple

    def __init__(self, action='delete:', *args, **kwargs):
        self.action = action
        super().__init__(*args, **kwargs)
        self.widget.attrs={"class": "down hidden"}
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

    def __init__(self, hours=[*range(24)], **kwargs):
        self.hours=hours
        self.widget=WeekInput(hours=hours)
        super().__init__(
            error_messages={
                'incomplete': 'incomplete',
                'invalid': 'required',
                'required': 'required',
            },
            fields=(
                [DayField(label=name, hours=hours, required=False, weekday=day) 
                for day, name in enumerate(WEEK_DAYS)]
            ),
            require_all_fields=False,
            template_name='field.html',
            **kwargs
        )

    def compress(self, data_list):
        return Hours24x7(data_list)
