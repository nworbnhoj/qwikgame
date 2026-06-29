import logging
from django.forms import CharField, ChoiceField, RadioSelect
from django.utils.translation import gettext_lazy as _
from feedback.models import Feedback
from qwikgame.forms import QwikForm
from qwikgame.widgets import TextArea


logger = logging.getLogger(__file__)


class FeedbackForm(QwikForm):
    type = ChoiceField(
        choices=list(Feedback.TYPE.items()),
        label=_('Type'),
        required=True,
        template_name='field.html',
        widget=RadioSelect,
    )
    text = CharField(
        label=_('Description'),
        required=True,
        template_name='field.html',
        widget=TextArea(attrs={'placeholder': _(
            'thanks heaps for taking the time!')}),
    )

    @classmethod
    def get(klass):
        return {'feedback_form': klass()}

    @classmethod
    def post(klass, request_post):
        form = klass(data=request_post)
        context = {'feedback_form': form}
        if form.is_valid():
            context |= {k: v for k, v in form.cleaned_data.items()}
        return context
