import logging
from django.db import models
from django.db.models import Sum
from game.models import Game
from venue.models import Place
from player.models import Filter
from qwikgame.constants import ADMIN1, COUNTRY, LAT, LNG, LOCALITY, NAME, SIZE
from service.locate import Locate

logger = logging.getLogger(__file__)

class Mark(models.Model):
    game = models.ForeignKey(Game, on_delete=models.CASCADE, blank=True, null=True)
    place = models.ForeignKey(Place, on_delete=models.CASCADE)
    size = models.PositiveIntegerField(default=0)

    def save(self, **kwargs):
        self.update_size()
        super().save(**kwargs)
        logger.debug(f'Mark save: {self}')
        # recursively add Region Marks as required
        regions = Region.objects
        if hasattr(self, 'venue'):
            place = self.venue.place_dict()
        elif hasattr(self, 'region'):
            place = self.region.place_dict()
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
    def place_filter(place):
        return {k: v for k, v in place.items() if v is not None}    

    def key(self):
        key = self.country
        if self.admin1:
            key = f'{self.admin1}|{key}'
            if self.locality:
                key = f'{self.locality}|{key}'
                if hasattr(self, 'venue'):
                    key = f'{self.name}|{key}'
        return key

    def mark(self):
        if hasattr(self, 'venue'):
            return self.venue.mark() | { SIZE: self.size }
        elif hasattr(self, 'region'):
            return self.region.mark() | { SIZE: self.size }
        logger.warn('Mark not venue or region:'.format(self.id))
        return None

    def parent(self):
        mark_qs = Mark.objects.filter(game=self.game, place__country=self.country)
        mark_qs = mark_qs.select_related('place__region')
        if self.venue:
            mark_qs = mark_qs.filter(place__admin1=self.admin1)
            mark_qs = mark_qs.filter(place__locality=self.locality)
            return mark_qs.first()
        if self.locality:
            mark_qs = mark_qs.exclude(place__locality__isnull=False)
            return mark_qs.filter(place__admin1=self.admin1).first()
        if self.admin1:
            return mark_qs.exclude(place__admin1__isnull=False).first()
        return None

    def place_dict(self):
        place = { COUNTRY: self.country }
        if self.admin1:
            place[ADMIN1] = self.admin1
        if self.locality:
            place[LOCALITY] = self.locality
        return place

    # TODO call update_size() on add/delete Filter and add Match
    def update_size(self):
        old_size = self.size
        if hasattr(self, 'venue'):
            filter_qs = Filter.objects.filter(active=True, game=self.game, venue=self.venue)
            self.size = filter_qs.count()
            # TODO add historical match count in prior year
            # TODO make distinct per player
        elif hasattr(self, 'region'):
            mark_qs = Mark.objects.filter(game=self.game)
            if self.locality:
                mark_qs = mark_qs.filter(place__country=self.country)
                mark_qs = mark_qs.filter(place__admin1=self.admin1)
                mark_qs = mark_qs.filter(place__locality=self.locality)
                self.size = mark_qs.count()
            elif self.admin1:
                mark_qs = mark_qs.filter(place__country=self.country)
                mark_qs = mark_qs.filter(place__admin1=self.admin1)
                mark_qs = mark_qs.exclude(place__locality__isnull=True)
                self.size = mark_qs.aggregate(Sum('size', default=0)).get('size__sum', 0)
            else:
                mark_qs = mark_qs.filter(place__country=self.country)
                mark_qs = mark_qs.exclude(place__admin1__isnull=True)
                mark_qs = mark_qs.exclude(place__locality__isnull=True)
                self.size = mark_qs.aggregate(Sum('size', default=0)).get('size__sum', 0)
        if self.size != old_size:
            logger.debug(f'Mark update size: {self}')
            parent = self.parent()
            if parent:
                parent.save()
    