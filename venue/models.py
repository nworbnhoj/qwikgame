from django.db import models

from authenticate.models import User
from game.models import Game


class Manager(models.Model):
    location_auto = models.BooleanField(default=False)
    notify_email = models.BooleanField(default=True)
    notify_web = models.BooleanField(default=False)
    user = models.OneToOneField(User, on_delete=models.CASCADE)


class Venue(models.Model):
    games = models.ManyToManyField(Game)
    managers = models.ManyToManyField(Manager)
    name = models.CharField(max_length=128)
    url = models.URLField(max_length=256, blank=True)

    def __str__(self):
        return self.name