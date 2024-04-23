from django.forms import BooleanField, CheckboxSelectMultiple, ChoiceField, MultipleChoiceField, MultiValueField
from player.models import STRENGTH, WEEK_DAYS
from qwikgame.utils import bools_to_int, int_to_bytes3
from qwikgame.widgets import ActionMultiple, MultiWidget
from qwikgame.widgets import ActionMultiple, DayInput, RangeInput, SelectRangeInput, TabInput, WeekInput


class DayField(MultiValueField):

    def __init__(self, range=range(24), **kwargs):
        self.range = range
        self.widget=DayInput(range=range)
        super().__init__(
            error_messages={
                'incomplete': 'incomplete',
                'invalid': 'required',
                'required': 'required',
            },
            fields=(
                [BooleanField(label=hr, required=False)
                for hr in self.range]
            ),
            require_all_fields=False,
            **kwargs
        )

    def compress(self, data_list):
        bools = [False] * self.range[0]
        bools += data_list
        bools += [False] * (23 - self.range[-1])
        return int_to_bytes3(bools_to_int(bools))


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

    def __init__(self, range=range(24), **kwargs):
        self.range = range
        self.widget=WeekInput(range=range)
        super().__init__(
            error_messages={
                'incomplete': 'incomplete',
                'invalid': 'required',
                'required': 'required',
            },
            fields=(
                [DayField(label=day, range=self.range, required=False) 
                for day in WEEK_DAYS]
            ),
            require_all_fields=False,
            template_name='field.html',
            **kwargs
        )

    def compress(self, data_list):
        result = bytearray()
        for data in data_list:
            result += data
        return result
