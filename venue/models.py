from django.db import models


class Venue(models.Model):
    name = models.CharField(max_length=128)
    url = models.URLField(max_length=256, blank=True)

    def __str__(self):
        return self.name

