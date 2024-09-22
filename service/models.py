from django.db import models


class Service(models.Model):
    name = models.CharField(max_length=32, primary_key=True)
    url = models.URLField(max_length=64)
    key = models.CharField(max_length=64)