import logging
from django.forms import CheckboxInput, CheckboxSelectMultiple, MultiWidget
from django.forms.widgets import Input, Select
from qwikgame.constants import WEEK_DAYS
from qwikgame.hourbits import Hours24, Hours24x7

logger = logging.getLogger(__file__)

DAY_ALL = b'\xff\xff\xff'
DAY_NONE = bytes(3)
WEEK_ALL = b'\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff'
WEEK_NONE = bytes(21)

class ActionMultiple(CheckboxSelectMultiple):
    attrs = {"class": "down hidden"}
    use_fieldset=False


class DayInput(MultiWidget):
    template_name='input_day.html'
    use_fieldset=False

    def __init__(self, attrs={}, label='', hours=[*range(24)]):
        self.label=label
        self.hours=hours
        widgets = []
        widget_attrs = { 'class': 'hidden', 'type': 'checkbox' }
        for hr in range(24):
            widgets.append(HourInput(label=hr, attrs=widget_attrs))
        for hr in hours:
            widgets[hr].attrs['class'] = ''
        super().__init__(
            attrs = attrs,
            widgets=(widgets)
        )

    def decompress(self, hours24=DAY_NONE):
        return Hours24(hours24).as_bools()

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
            widgets=[DayInput(
                label=name,
                hours=hours,
                attrs={'data-weekday':day})
            for day, name in enumerate(WEEK_DAYS)]
        )

    def decompress(self, hours168=WEEK_NONE):
        return Hours24x7(hours168).as_days7()