import logging
from django.db import models


logger = logging.getLogger(__file__)


class Service(models.Model):
	name = models.CharField(max_length=32, primary_key=True)
	url = models.URLField(max_length=64)
	key = models.CharField(max_length=64)