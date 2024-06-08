import pytz
from authenticate.models import User
from django.db import models
from pytz import datetime, timezone


TIMEZONES = tuple(zip(pytz.all_timezones, pytz.all_timezones))


class Manager(models.Model):
    user = models.OneToOneField(User, on_delete=models.CASCADE)

    def __str__(self):
        return self.user.person.name


class Venue(models.Model):
    games = models.ManyToManyField('game.Game')
    managers = models.ManyToManyField(Manager, blank=True)
    name = models.CharField(max_length=128)
    url = models.URLField(max_length=256, blank=True)
    tz = models.CharField(max_length=32, choices=TIMEZONES, default='UTC')

    @classmethod
    def choices(klass):
        try:
            return {venue.pk: venue.name for venue in klass.objects.all()}
        except:
            return {}

    def __str__(self):
        return self.name

    # returns an aware datetime based in the venue timezone
    def datetime(self, date, time=datetime.time(hour=0)):
        naive = datetime.datetime.combine(date, time)
        aware = self.tzinfo().localize(naive)
        return aware

    def tzinfo(self):
        return timezone(self.tz)