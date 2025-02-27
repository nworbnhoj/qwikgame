import logging
from django.db import models


logger = logging.getLogger(__file__)


class Feedback(models.Model):

    TYPE = {
        '_': '',
        'B': 'Bug',
        'U': 'Something is not clear',
        'D': 'Something is too dfficult',
        'G': 'Something is really good',
        'I': 'Suggestion for improvement',
        'O': 'Other',
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
