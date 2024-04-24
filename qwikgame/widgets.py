from django.forms import CheckboxInput, CheckboxSelectMultiple, MultiWidget
from django.forms.widgets import Input, Select
from player.models import STRENGTH, WEEK_DAYS
from qwikgame.utils import bytes3_to_int, int_to_bools24

class ActionMultiple(CheckboxSelectMultiple):
    attrs = {"class": "down hidden"}
    use_fieldset=False


class DayInput(MultiWidget):
    template_name='input_day.html'
    use_fieldset=False

    def __init__(self, label='', hours=[*range(24)], **kwargs):
        self.label=label
        self.hours=hours
        widgets = []
        attrs={'class': 'hidden', 'type': 'radio'}
        for hr in range(24):
            widgets.append(HourInput(label=hr, attrs=attrs))
        for hr in hours:
            widgets[hr].attrs['class'] = ''
        super().__init__(
            widgets=(widgets)
        )

    def decompress(self, bytes3):
        return int_to_bools24(bytes3_to_int(bytes3))

    def get_context(self, name, value, attrs):
        context = super().get_context(name, value, attrs)
        context['widget']['label'] = self.label
        return context


class HourInput(CheckboxInput):
    template_name='input_hour.html'
    require_all_fields=False,
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

    def __init__(self, hours=[*range(24)], **kwargs):
        self.hours = hours
        super().__init__(
            widgets=[DayInput(label=day, hours=hours) for day in WEEK_DAYS]
        )

    def decompress(self, bytes21):
        if isinstance(bytes21, bytes) and len(bytes21) == 21:
            return [bytes21[i: i+3] for i in range(0, 21, 3)]
        return [bytearray(3) for day in range(7)]