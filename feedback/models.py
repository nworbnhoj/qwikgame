import logging
from django.db import models


logger = logging.getLogger(__file__)


class Feedback(models.Model):

    date = models.DateTimeField()
    text = models.TextField()
