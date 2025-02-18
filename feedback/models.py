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

    date = models.DateTimeField(auto_now_add=True, editable=False)
    path = models.CharField(editable=False, max_length=128)
    text = models.TextField()
    type = models.CharField(max_length=1, choices=TYPE, default='_')
    version = models.CharField(editable=False, max_length=32)
