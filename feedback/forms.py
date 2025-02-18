import logging
from feedback.models import Feedback
from django.forms import ModelForm


logger = logging.getLogger(__file__)


class FeedbackForm(ModelForm):

    class Meta:
        model = Feedback
        fields = ['type', 'text']

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