import logging
from django.forms import ModelForm, Select, Textarea
from django.utils.translation import gettext_lazy as _
from feedback.models import Feedback


logger = logging.getLogger(__file__)


class FeedbackForm(ModelForm):

    class Meta:
        model = Feedback
        fields = ['type', 'text']
        labels = { 'text': _('Description') }
        widgets = {
            'text': Textarea(attrs={'placeholder': _('thanks heaps for taking the time!')}),
            'type': Select(attrs={'class': _('feedback')}),
        }

    @classmethod
    def get(klass):
        return { 'feedback_form': klass() }

    @classmethod
    def post(klass, request_post):
        form = klass(data=request_post)
        context = { 'feedback_form': form }
        if form.is_valid():
            context |= { k:v for k,v in form.cleaned_data.items() }
        return context