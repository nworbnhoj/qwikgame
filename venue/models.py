from django.db import models

from authenticate.models import User
from game.models import Game


class Manager(models.Model):
    user = models.OneToOneField(User, on_delete=models.CASCADE)

    def __str__(self):
        return self.user.person.name


class Venue(models.Model):
    games = models.ManyToManyField(Game)
    managers = models.ManyToManyField(Manager, blank=True)
    name = models.CharField(max_length=128)
    url = models.URLField(max_length=256, blank=True)

    def __str__(self):
        return self.name