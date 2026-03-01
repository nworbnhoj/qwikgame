import logging
from django.db import models
from django.utils.translation import gettext_lazy as _


logger = logging.getLogger(__file__)


class Feedback(models.Model):

    TYPE = {
        '_': '',
        'B': _('Bug'),
        'U': _('Something is not clear'),
        'D': _('Something is too dfficult'),
        'G': _('Something is really good'),
        'I': _('Suggestion for improvement'),
        'T': _('Translation issue'),
        'O': _('Other'),
    }

    commit = models.CharField(editable=False, max_length=40)
    date = models.DateTimeField(auto_now_add=True, editable=False)
    issue = models.PositiveIntegerField(blank=True, null=True)
    path = models.CharField(editable=False, max_length=128)
    text = models.TextField()
    type = models.CharField(max_length=1, choices=TYPE, default='_')
    version = models.CharField(editable=False, max_length=32)

    @property
    def type_str(self):
    	return Feedback.TYPE[self.type]
