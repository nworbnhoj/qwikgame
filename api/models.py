import logging
from django.db import models
from django.db.models import Sum
from game.models import Game
from venue.models import Place, Region
from player.models import Filter
from qwikgame.constants import ADMIN1, COUNTRY, GAME, LAT, LNG, LOCALITY, NAME, SIZE
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
        country = self.place.country
        admin1 = self.place.admin1
        locality = self.place.locality
        region = None
        try:
            if self.place.is_venue:
                region = Region.objects.filter(country=country, admin1=admin1, locality=locality).first()
                if not region:
                    region = Region.from_place(country=country, admin1=admin1, locality=locality)
            elif self.place.is_region:
                if locality:
                    region = Region.objects.filter(country=country, admin1=admin1, locality__isnull=True).first()
                    if not region:
                        region = Region.from_place(country=country, admin1=admin1)
                elif admin1:
                    region = Region.objects.filter(country=country, admin1__isnull=True, locality__isnull=True).first()
                    if not region:
                        region = Region.from_place(country=country)
            if region and not region.pk:
                region.save()
                logger.info(f'Region new: {region}')
        except:
            logger.exception('failed to create Region')
        if region:
            try:
                if not Mark.objects.filter(place=region.place_ptr, game=self.game).exists():
                    Mark(game=self.game, place=region.place_ptr).save()
                if not Mark.objects.filter(place=region.place_ptr, game__isnull=True).exists():
                    Mark(place=region.place_ptr).save()
            except:
                logger.exception('failed to create Mark')

    def __str__(self):

        return '{} [{}] {}'.format(
            self.game,
            self.size,
            self.place.name,
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
        key = self.place.country
        if self.place.admin1:
            key = f'{self.place.admin1}|{key}'
            if self.place.locality:
                key = f'{self.place.locality}|{key}'
                if self.place.is_venue:
                    key = f'{self.place.name}|{key}'
        return key

    def mark(self):
        mark = { SIZE: self.size }
        if self.game:
            mark |= { GAME: self.game.code }
        if self.place.is_venue:
            mark |= self.place.venue.mark()
        elif self.place.is_region:
            mark |= self.place.region.mark()
        else:
            logger.warn('Mark not venue or region:'.format(self.id))
            return None
        return mark;

    def parent(self):
        logger.warn(self)
        mark_qs = Mark.objects.filter(game=self.game, place__country=self.place.country)
        mark_qs = mark_qs.select_related('place__region')
        mark_qs = mark_qs.filter(place__region__isnull=False)
        if self.place.is_venue:
            mark_qs = mark_qs.filter(place__admin1=self.place.admin1)
            mark_qs = mark_qs.filter(place__locality=self.place.locality)
            return mark_qs.first()
        if self.place.locality:
            mark_qs = mark_qs.exclude(place__locality__isnull=False)
            return mark_qs.filter(place__admin1=self.place.admin1).first()
        if self.place.admin1:
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
        place = self.place
        if place.is_venue:
            filter_qs = Filter.objects.filter(active=True, game=self.game, place=place)
            self.size = filter_qs.count()
            # TODO add historical match count in prior year
            # TODO make distinct per player
        elif place.is_region:
            mark_qs = Mark.objects.filter(game=self.game)
            if place.locality:
                mark_qs = mark_qs.filter(place__country=place.country)
                mark_qs = mark_qs.filter(place__admin1=place.admin1)
                mark_qs = mark_qs.filter(place__locality=place.locality)
                self.size = mark_qs.count()
            elif place.admin1:
                mark_qs = mark_qs.filter(place__country=place.country)
                mark_qs = mark_qs.filter(place__admin1=place.admin1)
                mark_qs = mark_qs.exclude(place__locality__isnull=True)
                self.size = mark_qs.aggregate(Sum('size', default=0)).get('size__sum', 0)
            else:
                mark_qs = mark_qs.filter(place__country=place.country)
                mark_qs = mark_qs.exclude(place__admin1__isnull=True)
                mark_qs = mark_qs.exclude(place__locality__isnull=True)
                self.size = mark_qs.aggregate(Sum('size', default=0)).get('size__sum', 0)
        if self.size != old_size:
            logger.debug(f'Mark update size: {self}')
            parent = self.parent()
            if parent:
                parent.save()
    