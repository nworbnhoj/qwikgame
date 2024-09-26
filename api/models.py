import logging
from django.db import models
from django.db.models import Sum
from game.models import Game
from venue.models import Venue
from player.models import Filter
from qwikgame.constants import ADMIN1, COUNTRY, EAST, LAT, LNG, LOCALITY, NAME, NORTH, SIZE, SOUTH, WEST
from service.locate import Locate

logger = logging.getLogger(__file__)


class Region(models.Model):
    admin1 = models.CharField(max_length=64, blank=True, null=True)
    country = models.CharField(max_length=2)
    east = models.DecimalField(max_digits=9, decimal_places=6, default=180)
    name = models.CharField(max_length=128, blank=True)
    lat = models.DecimalField(max_digits=9, decimal_places=6, default=0)
    lng = models.DecimalField(max_digits=9, decimal_places=6, default=0)
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


class Mark(models.Model):
    game = models.ForeignKey(Game, on_delete=models.CASCADE, blank=True, null=True)
    region = models.ForeignKey(Region, on_delete=models.CASCADE, blank=True, null=True)
    size = models.PositiveIntegerField(default=0)
    venue = models.ForeignKey(Venue, on_delete=models.CASCADE, blank=True, null=True)

    def save(self, **kwargs):
        self.update_size()
        super().save(**kwargs)
        logger.debug(f'Mark save: {self}')
        # recursively add Region Marks as required
        regions = Region.objects
        if self.venue:
            place = self.venue.place()
        elif self.region:
            place = self.region.place()
            if place.pop(LOCALITY, None):
                regions = regions.exclude(locality__isnull=False)
            elif place.pop(ADMIN1, None):
                regions = regions.exclude(admin1__isnull=False)
        region = regions.filter(**place).first()
        if not region:
            try:
                region = Region.from_place(**place)
                region.save()
                logger.info(f'Region new: {region}')
            except:
                logger.exception('failed to create Region')
        if region and not Mark.objects.filter(region=region, game=self.game).exists():
            try:
                Mark(game=self.game, region=region).save()
            except:
                logger.exception('failed to create Mark')


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

    @property
    def country(self):
        if self.venue:
            return self.venue.country
        elif self.region:
            return self.region.country
        logger.warn('Mark missing both region and venue')
        return None

    @property
    def admin1(self):
        if self.venue:
            return self.venue.admin1
        elif self.region:
            return self.region.admin1
        logger.warn('Mark missing both region and venue')
        return None

    @property
    def locality(self):
        if self.venue:
            return self.venue.locality
        elif self.region:
            return self.region.locality
        logger.warn('Mark missing both region and venue')
        return None
    

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
        mark_qs = Mark.objects.filter(game=self.game)
        mark_qs = mark_qs.exclude(venue__isnull=False)
        if self.venue:
            kwargs = Mark.region_filter(self.venue.place())
            return mark_qs.filter(**kwargs).first()
        place = self.region.place()
        if place.pop(LOCALITY, None):            
            kwargs = Mark.region_filter(place)
            mark_qs = mark_qs.filter(**kwargs)
            mark_qs = mark_qs.exclude(region__locality__isnull=False)
            return mark_qs.first()
        if place.pop(ADMIN1, None):
            kwargs = Mark.region_filter(place)
            mark_qs = mark_qs.filter(**kwargs)
            mark_qs = mark_qs.exclude(region__admin1__isnull=False)
            return mark_qs.first()
        return None

    def place(self):
        place = { COUNTRY: self.country }
        if self.admin1:
            place[ADMIN1] = self.admin1
        if self.locality:
            place[LOCALITY] = self.locality
        return place

    # TODO call update_size() on add/delete Filter and add Match
    def update_size(self):
        place = None
        old_size = self.size
        if self.venue:
            self.size = Filter.objects.filter(active=True, game=self.game, venue=self.venue).count()
            # TODO add historical match count in prior year
            # TODO make distinct per player
        elif self.region:
            size = 0
            place = self.region.place()
            mark_qs = Mark.objects.filter(game=self.game)
            if place.get(LOCALITY):
                kwargs = Mark.venue_filter(place)
                self.size = mark_qs.filter(**kwargs).count()
            elif place.get(ADMIN1):
                kwargs = Mark.region_filter(place)
                mark_qs.filter(**kwargs)
                mark_qs.exclude(region__locality__isnull=True)
                self.size = mark_qs.aggregate(Sum('size', default=0)).get('size__sum', 0)
            else:
                kwargs = Mark.region_filter(place)
                mark_qs.filter(**kwargs)
                mark_qs.exclude(region__locality__isnull=True)
                mark_qs.exclude(region__admin1__isnull=True)
                self.size = mark_qs.aggregate(Sum('size', default=0)).get('size__sum', 0)
        if self.size != old_size:
            logger.info(f'Mark update size: {self}')
            parent = self.parent()
            if parent:
                parent.save()
    