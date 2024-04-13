from django.forms import Form


class QwikForm(Form):
    required_css_class = "required"

    def __init__(self, *args, **kwargs):
        kwargs.setdefault('label_suffix', '')  
        super().__init__(*args, **kwargs)