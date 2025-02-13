from django.forms import Form


class QwikForm(Form):
    required_css_class = "required"

    def __init__(self, *args, **kwargs):
        kwargs.setdefault('label_suffix', '')  
        super().__init__(*args, **kwargs)


class MenuForm(QwikForm):

    @classmethod
    def get(klass):
        return { 'menu_form': klass()}

    @classmethod
    def post(klass, request_post):
        form = klass(data=request_post)
        context = {'menu_form': form}
        if form.is_valid():
            context |= {k:v for k,v in request_post.items()}
        return context