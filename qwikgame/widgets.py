from django.forms import CheckboxInput, CheckboxSelectMultiple, MultiWidget
from django.forms.widgets import Input, Select
from player.models import ENDIAN, STRENGTH, WEEK_DAYS
from qwikgame.utils import int_to_bools24

class ActionMultiple(CheckboxSelectMultiple):
    attrs = {"class": "down hidden"}
    use_fieldset=False


class DayInput(MultiWidget):
    template_name='input_day.html'
    use_fieldset=False

    def __init__(self, label='', range=range(24), **kwargs):
        self.label=label
        self.range=range
        super().__init__(
            widgets=([HourInput(label=hr) for hr in self.range])
        )

    def decompress(self, value):
        if isinstance(value, bytes) and len(value) == 3:
            return int_to_bools24(int.from_bytes(value, ENDIAN))[self.range[0]: self.range[-1]+1]
        return [False for hr in self.range]

    def get_context(self, name, value, attrs):
        context = super().get_context(name, value, attrs)
        context['widget']['label'] = self.label
        return context


class HourInput(CheckboxInput):
    template_name='input_hour.html'
    label=''

    def __init__(self, label='', **kwargs):
        self.label=label
        super().__init__(**kwargs)

    def get_context(self, name, value, attrs):
        context = super().get_context(name, value, attrs)
        context['widget']['label'] = self.label
        return context


class IconSelectMultiple(CheckboxSelectMultiple):
    option_template_name='option_game.html'
    use_fieldset=False

    def __init__(self, icons={}, *args, **kwargs):
        self.icons = icons
        super().__init__(*args, **kwargs)

    def create_option(self, *args, **kwargs):
        option = super().create_option(*args, **kwargs)
        option['icon'] = self.icons[option["value"]]
        return option


class RangeInput(Input):
    checked_attribute = {"checked": True}
    input_type='range'
    template_name='range.html'

    def __init__(self, attrs=None, choices=()):
        super().__init__(attrs)
        self.attrs = {'oninput':'slide(this)'}
        self.choices = choices

    def get_context(self, name, value, attrs):
        context = super().get_context(name, value, attrs)
        context["widget"]["choices"] = self.choices
        return context


class SelectRangeInput(RangeInput):
    template_name='select_range.html'


class TabInput(MultiWidget):
    template_name='input_tab.html'
    use_fieldset=False

    def decompress(self, value):
        if value:
            return [val for val in value]
        return [True for tab in self.widgets]


class WeekInput(MultiWidget):
    template_name='input_week.html'
    use_fieldset=False

    def __init__(self, range=range(24), **kwargs):
        self.range = range
        super().__init__(
            widgets=[DayInput(label=day, range=self.range) for day in WEEK_DAYS]
        )

    def decompress(self, value):
        if isinstance(value, bytes) and len(value) == 21:
            return [value[i: i+3] for i in range(0, 21, 3)]
        return [bytearray(3) for day in range(7)]