from django.forms import CheckboxInput, CheckboxSelectMultiple, MultiWidget


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
