import pytz
from authenticate.models import User
from django.db import models
from pytz import datetime, timezone
from qwikgame.constants import ADMIN1, COUNTRY, LAT, LNG, LOCALITY, NAME
from qwikgame.hourbits import Hours24x7

TIMEZONES = tuple(zip(pytz.all_timezones, pytz.all_timezones))


class Manager(models.Model):
    user = models.OneToOneField(User, on_delete=models.CASCADE)

    def __str__(self):
        return self.user.person.name


class Venue(models.Model):
    address = models.CharField(max_length=256, blank=True)
    admin1 = models.CharField(max_length=64, blank=True)
    country = models.CharField(max_length=2, blank=True)
    games = models.ManyToManyField('game.Game')
    hours = models.BinaryField(default=bytes(21))
    lat = models.DecimalField(max_digits=9, decimal_places=6, default=-36.449786)
    lng = models.DecimalField(max_digits=9, decimal_places=6, default=146.430037)
    locality = models.CharField(max_length=64, blank=True)
    managers = models.ManyToManyField(Manager, blank=True)
    name = models.CharField(max_length=128)
    note = models.TextField(max_length=256, blank=True)
    phone = models.CharField(max_length=12, blank=True)
    placeid = models.TextField(blank=True)
    route = models.CharField(max_length=64, blank=True)
    str_num = models.CharField(max_length=8, blank=True)
    suburb = models.CharField(max_length=32, blank=True)
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

    def hours_open(self):
        return Hours24x7(self.hours)

    def mark(self):
        return {
            LAT: self.lat,
            LNG: self.lng,
            NAME: self.name,
        }

    def place(self):
        kwargs = { COUNTRY: self.country }
        if self.admin1:
            kwargs[ADMIN1] = self.admin1
        if self.locality:
            kwargs[LOCALITY] = self.locality
        return kwargs

    def tzinfo(self):
        return timezone(self.tz)