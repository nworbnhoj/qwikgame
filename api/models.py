import logging, sys
from django.db import models
from django.db.models import Sum
from venue.models import Region, Venue
from player.models import Filter, Player
from qwikgame.constants import ADMIN1, COUNTRY, GAME, LAT, LNG, LOCALITY, NAME, NUM_PLAYER, NUM_VENUE
from service.locate import Locate

logger = logging.getLogger(__file__)

class Mark(models.Model):
    game = models.ForeignKey('game.Game', on_delete=models.CASCADE, blank=True, null=True)
    num_player = models.PositiveIntegerField(default=0)
    num_venue = models.PositiveIntegerField(default=0)
    place = models.ForeignKey('venue.Place', on_delete=models.CASCADE)

    def save(self, **kwargs):
        self.update_num_player()
        self.update_num_venue()
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
            self.num_player,
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

    def children(self):
        place = self.place
        if place.is_venue:
            return None
        mark_qs = self.descendents()
        if place.locality:
            return mark_qs
        if place.admin1:
            return mark_qs.exclude(place__venue__isnull=True)
        return mark_qs.exclude(place__locality__isnull=False)

    def descendents(self):
        place = self.place
        if place.is_venue:
            return None
        mark_qs = Mark.objects.exclude(pk=self.pk)
        mark_qs = mark_qs.filter(place__country=place.country)
        if place.admin1:
            mark_qs = mark_qs.filter(place__admin1=place.admin1)
        if place.locality:
            mark_qs = mark_qs.filter(place__locality=place.locality)
        if self.game:
            mark_qs = mark_qs.filter(game=self.game)
        return mark_qs

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
        mark = { NUM_PLAYER: self.num_player }
        if self.game:
            mark |= { GAME: self.game.code }
        if self.place.is_venue:
            mark |= self.place.venue.mark()
        elif self.place.is_region:
            mark |= self.place.region.mark()
            mark |= { NUM_VENUE: self.num_venue }
        else:
            logger.warn('Mark not venue or region:'.format(self.id))
            return None
        return mark;

    def non_game_mark(self):
        if not self.game:
            return self
        place = self.place
        mark_qs = Mark.objects.filter(game__isnull=True, place__country=place.country)
        if place.admin1:
            mark_qs = mark_qs.filter(place__admin1=place.admin1)
        else:
            mark_qs = mark_qs.filter(place__admin1__isnull=True)
        if place.locality:
            mark_qs = mark_qs.filter(place__locality=place.locality)
        else:
            mark_qs = mark_qs.filter(place__locality__isnull=True)
        return mark_qs.first()

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


    def update_num_venue(self):
        old_num_venue = self.num_venue
        place = self.place
        if place.is_region:
            venue_qs = Venue.objects.filter(country=place.country)
            if place.admin1:
                venue_qs = venue_qs.filter(admin1=place.admin1)
            if place.locality:
                venue_qs = venue_qs.filter(locality=place.locality)
            if self.game:
                venue_qs = venue_qs.filter(games__in=[self.game.pk])
            self.num_venue = venue_qs.distinct().count()
        if self.num_venue != old_num_venue:
            logger.info(f'Mark update num_venue: {self}')
            # update the num_venue of the parent region Mark
            parent = self.parent()
            if parent:
                parent.save()
            # update the num_venue of the non-game Mark
            if self.game:
                mark = self.non_game_mark()
                if mark:
                    mark.save()
                else:
                    logger.warn(f'failed to update non-game Mark: {self}')
    

    # TODO call update_num_player() on add/delete Filter and add Match
    def update_num_player(self):
        old_num_player = self.num_player
        place = self.place
        if place.is_venue:
            appeal_qs = Player.objects.filter(appeal__venue=place)
            bid_qs = Player.objects.filter(bid__appeal__venue=place)
            filter_qs = Player.objects.filter(filter__place=place)
            match_qs = Player.objects.filter(match__venue=place)
            if self.game:
                appeal_qs = appeal_qs.filter(appeal__game=self.game)
                bid_qs = bid_qs.filter(bid__appeal__game=self.game)
                filter_qs = filter_qs.filter(filter__game=self.game)
                match_qs = match_qs.filter(match__game=self.game)
            player_qs = appeal_qs | bid_qs | filter_qs | match_qs
            self.num_player = player_qs.distinct().count()
        elif place.is_region:
            aggregate = self.children().aggregate(Sum('num_player')).get('num_player__sum')
            self.num_player = aggregate if aggregate else 0
        if self.num_player != old_num_player:
            logger.info(f'Mark update num_player: {self}')
            # update the num_player of the parent region Mark
            parent = self.parent()
            if parent:
                parent.save()
            # update the num_player of the non-game Mark
            if self.game:
                mark = self.non_game_mark()
                if mark:
                    mark.save()
                else:
                    logger.warn(f'failed to update non-game Mark: {self}')
    