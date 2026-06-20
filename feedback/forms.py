import logging
from django.forms import ModelForm
from django.utils.translation import gettext_lazy as _
from feedback.models import Feedback
from qwikgame.widgets import TextArea, RadioSelect


logger = logging.getLogger(__file__)


class FeedbackForm(ModelForm):

    class Meta:
        model = Feedback
        fields = ['type', 'text']
        labels = {'text': _('Description')}
        localized_fields = ['__all__']
        widgets = {
            'text': TextArea(attrs={
                'class': _('feedback'),
                'placeholder': _('thanks heaps for taking the time!')
            }),
            'type': RadioSelect(attrs={
                'class': _('feedback')
            }),
        }

    @classmethod
    def get(klass):
        form = klass()
        form.fields['text'].template_name = 'field.html'
        form.fields['type'].template_name = 'field.html'
        return {'feedback_form': form}

    @classmethod
    def post(klass, request_post):
        form = klass(data=request_post)
        context = {'feedback_form': form}
        if form.is_valid():
            context |= {k: v for k, v in form.cleaned_data.items()}
        return context
