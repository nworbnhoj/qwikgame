import logging
from django.db import models
from django.db.models import Sum
from game.models import Game
from venue.models import Venue
from player.models import Filter
from qwikgame.constants import ADMIN1, COUNTRY, LAT, LNG, LOCALITY, NAME, SIZE
# from qwikgame.service import Locate

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

    def key(self):
        if self.venue:
            key = self.venue.country
            if self.venue.admin1:
                key = f'{self.venue.admin1}|{key}'
                if self.venue.locality:
                    key = f'{self.venue.locality}|{key}'
                    key = f'{self.venue.name}|{key}'
            return key
        elif self.region:
            key = self.region.country
            if self.region.admin1:
                key = f'{self.region.admin1}|{key}'
                if self.region.locality:
                    key = f'{self.region.locality}|{key}'
            return key
        logger.warn('Mark missing both region and venue')
        return None

    def mark(self):
        if self.venue:
            return self.venue.mark() | { SIZE: self.size }
        elif self.region:
            return self.region.mark() | { SIZE: self.size }
        logger.warn('Mark missing both venue and region:'.format(self.id))
        return None

    def parent(self):
        if self.venue:
            kwargs = Mark.region_filter(self.venue.place())
            return Mark.objects.filter(**kwargs).first()
        place = self.region.place()
        if place.pop(LOCALITY, None):
            kwargs = Mark.region_filter(place)
            return Mark.objects.filter(**kwargs).exclude(region__locality__isnull=False).first()
        if place.pop(ADMIN1, None):
            kwargs = Mark.region_filter(place)
            return Mark.objects.filter(**kwargs).exclude(region__admin1__isnull=False).first()
        return None

    # TODO call update_size() on add/delete Filter and add Match
    def update_size(self):
        place = None
        if self.venue:
            self.size = Filter.objects.filter(active=True, game=self.game, venue=self.venue).count()
            # TODO add historical match count in prior year
            # TODO make distinct per player
        elif self.region:
            size = None
            place = self.region.place()
            if place.get(LOCALITY):
                kwargs = Mark.venue_filter(place)
                marks = Mark.objects.filter(**kwargs)
                size = marks.aggregate(Sum('size'))
            elif place.get(ADMIN1):
                kwargs = Mark.region_filter(place)
                marks = Mark.objects.filter(**kwargs)
                marks = marks.exclude(region__locality__isnull=True)
                size = marks.aggregate(Sum('size'))
            else:
                kwargs = Mark.region_filter(place)
                marks = Mark.objects.filter(**kwargs)
                marks = marks.exclude(region__locality__isnull=False)
                marks = marks.exclude(region__admin1__isnull=True)
                size = marks.aggregate(Sum('size'))
            self.size = size['size__sum']
        self.save()
        logger.info('update size {} = {}'.format(place, self.size))
        parent = self.parent()
        if parent:
            parent.update_size()
    