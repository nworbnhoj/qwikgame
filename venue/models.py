import pytz
from authenticate.models import User
from django.db import models
from game.models import Game
from pytz import timezone


TIMEZONES = tuple(zip(pytz.all_timezones, pytz.all_timezones))


class Manager(models.Model):
    user = models.OneToOneField(User, on_delete=models.CASCADE)

    def __str__(self):
        return self.user.person.name


class Venue(models.Model):
    games = models.ManyToManyField(Game)
    managers = models.ManyToManyField(Manager, blank=True)
    name = models.CharField(max_length=128)
    url = models.URLField(max_length=256, blank=True)
    tz = models.CharField(max_length=32, choices=TIMEZONES, default='UTC')

    def __str__(self):
        return self.name

    def choices():
        return {venue.pk: venue.name for venue in Venue.objects.all()}

    def tzinfo(self):
        return timezone(self.tz)