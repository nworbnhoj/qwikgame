import logging
from django.db import models
from game.models import Game
from venue.models import Venue
from qwikgame.constants import ADMIN1, COUNTRY, LAT, LNG, LOCALITY, NAME, SIZE


logger = logging.getLogger(__file__)


class Region(models.Model):
    admin1 = models.CharField(max_length=64, blank=True, null=True)
    country = models.CharField(max_length=2)
    east = models.DecimalField(max_digits=9, decimal_places=6, default=180)
    name = models.CharField(max_length=128, blank=True)
    north = models.DecimalField(max_digits=9, decimal_places=6, default=90)
    south = models.DecimalField(max_digits=9, decimal_places=6, default=-90)
    west = models.DecimalField(max_digits=9, decimal_places=6, default=-180)
    locality = models.CharField(max_length=64, blank=True, null=True)

    def __str__(self):
        return '{}|{}|{} : {}'.format(
            self.country,
            self.admin1 if self.admin1 else '',
            self.locality if self.locality else '',
            self.name,
            )

    def mark(self):
        return {
            LAT: self.south + (self.north - self.south)/2,
            LNG: self.west + (self.east - self.west)/2,
            NAME: self.name,
        }

    def place(self):
        kwargs = { COUNTRY: self.country }
        if self.admin1:
            kwargs[ADMIN1] = self.admin1
        if self.locality:
            kwargs[LOCALITY] = self.locality
        return kwargs


class Mark(models.Model):
    game = models.ForeignKey(Game, on_delete=models.CASCADE, blank=True, null=True)
    region = models.ForeignKey(Region, on_delete=models.CASCADE, blank=True, null=True)
    size = models.PositiveIntegerField(default=0)
    venue = models.ForeignKey(Venue, on_delete=models.CASCADE, blank=True, null=True)

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    def __str__(self):

        return '{} [{}] {}'.format(
            self.game,
            self.size,
            self.venue.name if self.venue else self.region.name,
            )

    @classmethod
    def venue_mark(klass, venue):
        mark = klass(venue=venue)
        return mark

    @classmethod
    def region_mark(klass, region):
        mark = klass(region=region)
        return mark

    @staticmethod
    def region_filter(place):
        return {'region__'+k: v for k, v in place.items() if v is not None}

    @staticmethod
    def venue_filter(place):
        return {'venue__'+k: v for k, v in place.items() if v is not None}

    def mark(self):
        if self.venue:
            return self.venue.mark() | { SIZE: self.size }
        elif self.region:
            return self.region.mark() | { SIZE: self.size }
        logger.warn('Mark missing both venue and region:'.format(self.id))
        return None


class Service(models.Model):
    name = models.CharField(max_length=32, primary_key=True)
    url = models.URLField(max_length=64)
    key = models.CharField(max_length=64)
    