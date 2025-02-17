import logging
from django.db import models


logger = logging.getLogger(__file__)


class Feedback(models.Model):

    date = models.DateTimeField(auto_now_add=True)
    text = models.TextField()
