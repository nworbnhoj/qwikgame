from django.db import models

from game.models import Game
from player.models import Player
from venue.models import Venue


class Match(models.Model):
    date = models.DateTimeField()
    game = models.ForeignKey(Game, on_delete=models.CASCADE)
    rivals = models.ManyToManyField(Player)
    venue = models.ForeignKey(Venue, on_delete=models.CASCADE)

    def __str__(self):
        return "{} {} {}".format(self.rivals, self.date, self.venue)

