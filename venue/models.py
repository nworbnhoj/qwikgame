import logging, pytz
from authenticate.models import User
from django.db import models
from pytz import datetime, timezone
from qwikgame.constants import ADMIN1, COUNTRY, EAST, LAT, LNG, LOCALITY, NAME, NORTH, PLACEID, SIZE, SOUTH, WEST
from qwikgame.hourbits import Hours24x7, WEEK_NONE
from service.locate import Locate
# from api.models import Mark

logger = logging.getLogger(__file__)

TIMEZONES = tuple(zip(pytz.all_timezones, pytz.all_timezones))


class Manager(models.Model):
    user = models.OneToOneField(User, on_delete=models.CASCADE)

    def __str__(self):
        return self.user.person.name


class Place(models.Model):
    admin1 = models.CharField(max_length=64, blank=True, null=True)
    country = models.CharField(max_length=2, blank=True, null=True)
    name = models.CharField(max_length=128, blank=True)
    lat = models.DecimalField(max_digits=9, decimal_places=6, default=-36.449786)
    lng = models.DecimalField(max_digits=9, decimal_places=6, default=146.430037)
    placeid = models.TextField(blank=False, null=False)
    locality = models.CharField(max_length=64, blank=True, null=True)

    class Meta:
        constraints = [
            models.UniqueConstraint(fields=['placeid'], name='unique_placeid')
        ]

    def __str__(self):
        return self.name

    @property
    def is_region(self):
        return hasattr(self, 'region')

    @property
    def is_venue(self):
        return hasattr(self, 'venue')


class Region(Place):
    east = models.DecimalField(max_digits=9, decimal_places=6, default=180)
    north = models.DecimalField(max_digits=9, decimal_places=6, default=90)
    south = models.DecimalField(max_digits=9, decimal_places=6, default=-90)
    west = models.DecimalField(max_digits=9, decimal_places=6, default=-180)

    def __str__(self):
        return '{}|{}|{} : {}'.format(
            self.country,
            self.admin1 if self.admin1 else '',
            self.locality if self.locality else '',
            self.name,
            )

    def save(self, **kwargs):
        super().save(**kwargs)
        logger.debug(f'Region save: {self}')

    @classmethod
    def from_place(cls, country, admin1=None, locality=None):
        geometry = Locate.get_geometry(country, admin1, locality)
        if geometry:
            try:
                smallest = 'locality' if locality else 'admin1' if admin1 else 'country'
                location = geometry['location']
                viewport = geometry['viewport']
                northeast = viewport['northeast']
                southwest = viewport['southwest']
                region = cls(
                    admin1 = admin1,
                    country = country,
                    east = float(northeast['lng']),
                    lat = float(location['lat']),
                    lng = float(location['lng']),
                    locality = locality,
                    name = geometry['names'][smallest][:128],
                    north = float(northeast['lat']),
                    placeid = geometry['placeid'],
                    south = float(southwest['lat']),
                    west = float(southwest['lng']),
                    )
                return region
            except:
                logger.warn(f'invalid geometry for: {country}|{admin1}|{locality}\n{geometry}')
        logger.warn(f'failed to get geometry for: {country}|{admin1}|{locality}')
        return None

    def mark(self):
        return {
            EAST: self.east,
            LAT: self.lat,
            LNG: self.lng,
            NAME: self.name,
            NORTH: self.north,
            SOUTH: self.south,
            WEST: self.west,
        }

    def place(self):
        kwargs = { COUNTRY: self.country }
        if self.admin1:
            kwargs[ADMIN1] = self.admin1
        if self.locality:
            kwargs[LOCALITY] = self.locality
        return kwargs



class Venue(Place):
    address = models.CharField(max_length=256, blank=True)
    games = models.ManyToManyField('game.Game')
    hours = models.BinaryField(default=WEEK_NONE)
    managers = models.ManyToManyField(Manager, blank=True)
    note = models.TextField(max_length=256, blank=True)
    phone = models.CharField(max_length=12, blank=True)
    route = models.CharField(max_length=64, blank=True)
    str_num = models.CharField(max_length=8, blank=True)
    suburb = models.CharField(max_length=32, blank=True)
    url = models.URLField(max_length=256, blank=True)
    tz = models.CharField(max_length=32, choices=TIMEZONES, default='UTC')
    
    # def __init__(self, *args, **kwargs):
    #     super().__init__(*args, **kwargs)
    #     Mark(game=game, venue=self).save()

    def save(self, **kwargs):
        super().save(**kwargs)
        logger.debug(f'Venue save: {self}')

    @classmethod
    def from_placeid(cls, placeid):
        details = Locate.get_details(placeid)
        if details:
            logger.debug(f'google details for placeid:{placeid}\n{details}')
            # truncate CharField values to respect field.max_length
            for field in cls._meta.get_fields(include_parents=False):
                if field.get_internal_type() == 'CharField' and field.max_length and field.name in details.keys():
                    if field.max_length < len(details[field.name]):
                        logger.warn(f'truncated CharField Venue.{field.name}: {details[field.name]}')
                    details[field.name] = details[field.name][:field.max_length]
            venue = cls(**details)
            return venue
        logger.warn(f'invalid placeid: {placeid}')
        return None

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
            PLACEID: self.placeid,
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