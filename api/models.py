import logging, sys
from django.db import models
from django.db.models import Sum
from game.models import Game
from venue.models import Place, Region, Venue
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
        # recursively add parent-Marks if required
        try:
            parent = None
            if self.place.is_venue:
                parent = self.place.venue.region
            elif self.place.is_region:
                parent = self.place.region.parent
            if parent and not Mark.objects.filter(
                game__isnull=True,
                place = parent).exists():
                Mark(place = parent.place_ptr).save()
            if parent and not Mark.objects.filter(
                place = parent,
                game = self.game).exists():
                Mark(
                    game = self.game,
                    place = parent.place_ptr).save()
        except:
            logger.exception('failed to create parent Mark')

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

    @classmethod
    def refresh_marks(klass):
        print(f'deleting all {Mark.objects.count()} marks')
        for mark in Mark.objects.all():
            mark.delete()
        print(f'recreating marks for {Venue.objects.count()} venues')
        for venue in Venue.objects.all():
            progress = 'v'
            venue.save()
            for game in venue.games.all():
                Mark.objects.get_or_create(game=game, place=venue)
                progress += '.'
                locality = venue.region
                if locality:
                    Mark.objects.get_or_create(game=game, place=locality)
                    progress += '.'
                    admin1 = locality.parent
                    if admin1:
                        Mark.objects.get_or_create(game=game, place=admin1)
                        progress += '.'
                        country = admin1.parent
                        if country:
                            Mark.objects.get_or_create(game=game, place=country)
                            progress += '.'
                        else:
                            logger.warn(f'missing admin1 country: {admin1}')
                    else:
                        logger.warn(f'missing locality admin1: {locality}')
                else:
                    logger.warn(f'missing venue region: {venue}')
            sys.stdout.write(progress)
            sys.stdout.flush()
        sys.stdout.write('\n')

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
            filter_qs = Filter.objects.filter(active=True, place=place)
            if self.game:
                filter_qs = filter_qs.filter(game=self.game)
            self.size = filter_qs.count()
            # TODO add historical match count in prior year
            # TODO make distinct per player
        elif place.is_region:
            venue_qs = Venue.objects.filter(country=place.country)
            if place.admin1:
                venue_qs = venue_qs.filter(admin1=place.admin1)
            if place.locality:
                venue_qs = venue_qs.filter(locality=place.locality)
            if self.game:
                venue_qs = venue_qs.filter(games__in=[self.game.pk])
            self.size = venue_qs.distinct().count()
        if self.size != old_size:
            logger.info(f'Mark update size: {self}')
            # update the size of the parent region Mark
            parent = self.parent()
            if parent:
                parent.save()
            # update the size of the non-game Mark
            if self.game:
                mark_qs = Mark.objects.filter(game__isnull=True, place__country=place.country)
                if place.admin1:
                    mark_qs = mark_qs.filter(place__admin1=place.admin1)
                else:
                    mark_qs = mark_qs.filter(place__admin1__isnull=True)
                if place.locality:
                    mark_qs = mark_qs.filter(place__locality=place.locality)
                else:
                    mark_qs = mark_qs.filter(place__locality__isnull=True)
                mark = mark_qs.first()
                if mark:
                    mark.save()
                else:
                    logger.warn(f'failed to update non-game Mark: {self}')
    