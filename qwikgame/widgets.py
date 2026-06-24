import logging
from django.forms.widgets import CheckboxInput, CheckboxSelectMultiple, Input, MultiWidget, RadioSelect, Select, Textarea, TextInput
from qwikgame.constants import TWO_DAYS, WEEK_DAYS
from qwikgame.hourbits import Hours24, Hours24x7

logger = logging.getLogger(__file__)

DAY_ALL = b'\xff\xff\xff'
DAY_NONE = bytes(3)
WEEK_ALL = b'\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff'
WEEK_NONE = bytes(21)


class ActionMultiple(CheckboxSelectMultiple):
    attrs = {"class": "down left hidden"}
    use_fieldset = False


class CheckboxList(CheckboxSelectMultiple):
    template_name = "checkbox_list.html"
    option_template_name = "option_delete.html"


class DataSelect(RadioSelect):
    data_attr = {}

    def __init__(self, data_attr={}, *args, **kwargs):
        self.data_attr = data_attr
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


class DayInputMulti(CheckboxSelectMultiple):
    option_template_name = 'input_hour.html'
    template_name = 'input_day.html'
    use_fieldset = False

    def __init__(self, hours_enable=[*range(24)], hours_show=[*range(24)], label='', *args, **kwargs):
        self.data_attrs = {}
        self.hours_enable = hours_enable
        self.hours_show = hours_show
        self.label = label
        super().__init__(*args, **kwargs)

    def set_data_attr(self, key, value):
        self.data_attrs[f'data-{key}'] = value

    def create_option(
        self, name, value, label, selected, index, subindex=None, attrs=None
    ):
        option = super().create_option(name, value, label, selected, index, subindex, attrs)
        val = int(option['value'])
        if not val in self.hours_show:
            option['attrs']["class"] = 'hidden'
        if not val in self.hours_enable:
            option['attrs']['disabled'] = 'disabled'
        return option

    def get_context(self, name, value, attrs):
        attrs |= self.data_attrs
        context = super().get_context(name, value, attrs)
        context['widget']['label'] = self.label
        return context

    def set_hours_enable(self, hours):
        self.hours_enable = hours

    def set_hours_show(self, hours):
        self.hours_show = hours


class HourInput(CheckboxInput):
    template_name = 'input_hour.html'
    require_all_fields = False,
    label = ''
    input_type = 'radio'

    def __init__(self, input_type='checkbox', label='hr', *args, **kwargs):
        self.input_type = input_type
        self.label = label
        super().__init__(*args, **kwargs)

    def get_context(self, name, value, attrs):
        context = super().get_context(name, value, attrs)
        context['widget']['label'] = self.label
        return context


class DayInputRadio(RadioSelect):
    data_attrs = {}
    hours_enable = [*range(24)]
    hours_show = [*range(24)]
    option_template_name = 'input_hour.html'
    template_name = 'input_hour_radio.html'

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.use_fieldset = False

    def set_data_attr(self, key, value):
        self.data_attrs[f'data-{key}', value]

    def create_option(
        self, name, value, label, selected, index, subindex=None, attrs=None
    ):
        option = super().create_option(name, value, label, selected, index, subindex, attrs)
        val = int(option['value'])
        if not val in self.hours_show:
            option['attrs']["class"] = 'hidden'
        if not val in self.hours_enable:
            option['attrs']['disabled'] = 'disabled'
        return option

    def set_hours_enable(self, hours):
        self.hours_enable = hours

    def set_hours_show(self, hours):
        self.hours_show = hours

    def render(self, name, value, attrs=None, renderer=None):
        attrs |= self.data_attrs
        return super().render(name, value, attrs, renderer)


class IconSelectMultiple(CheckboxSelectMultiple):
    option_template_name = 'option_game.html'
    use_fieldset = False

    def __init__(self, icons={}, *args, **kwargs):
        self.icons = icons
        super().__init__(*args, **kwargs)

    def create_option(self, *args, **kwargs):
        option = super().create_option(*args, **kwargs)
        option['icon'] = self.icons[option["value"]]
        return option


class TextArea(Textarea):
    template_name = 'input_textarea.html'


class TextInput(TextInput):
    template_name = 'input_text.html'


class TwodayInput(MultiWidget):
    CHOICES = [(str(hr), str(hr)) for hr in range(24)]
    template_name = 'input_twoday.html'
    use_fieldset = False

    def __init__(
        self,
        hours_enable=[*range(24)],
        hours_show=[*range(24)],
        *args,
        **kwargs
    ):
        widgets = [DayInputMulti(
            choices=TwodayInput.CHOICES,
            hours_enable=hours_enable,
            hours_show=hours_show,
            label=weekday,
        )
            for weekday in TWO_DAYS
        ]
        wd = 0
        for widget in widgets:
            widget.set_data_attr('offsetday', wd)
            wd += 1
        super().__init__(widgets=widgets, *args, **kwargs)

    def decompress(self, hours168=WEEK_NONE):
        return Hours24x7(hours168).as_days7()

    def set_hours_enable(self, offsetday, hours):
        self.widgets[offsetday].hours_enable = hours

    def set_hours_show(self, offsetday, hours):
        self.widgets[offsetday].hours_show = hours


class WeekInput(MultiWidget):
    CHOICES = [(str(hr), str(hr)) for hr in range(24)]
    template_name = 'input_week.html'
    use_fieldset = False

    def __init__(
        self,
        hours_enable=[*range(24)],
        hours_show=[*range(24)],
        *args,
        **kwargs
    ):
        widgets = [DayInputMulti(
            choices=WeekInput.CHOICES,
            hours_enable=hours_enable,
            hours_show=hours_show,
            label=weekday,
        )
            for weekday in WEEK_DAYS
        ]
        wd = 0
        for widget in widgets:
            widget.set_data_attr('weekday', wd)
            wd += 1
        super().__init__(widgets=widgets, *args, **kwargs)

    def decompress(self, hours168=WEEK_NONE):
        return Hours24x7(hours168).as_days7()
