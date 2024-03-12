from django.db import models

from game.models import Game


class Venue(models.Model):
    games = models.ManyToManyField(Game)
    name = models.CharField(max_length=128)
    url = models.URLField(max_length=256, blank=True)

    def __str__(self):
        return self.name

