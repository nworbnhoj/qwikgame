import logging
from django.db import models


logger = logging.getLogger(__file__)


class Feedback(models.Model):

    date = models.DateTimeField(auto_now_add=True, editable=False)
    path = models.CharField(editable=False, max_length=128)
    text = models.TextField()
