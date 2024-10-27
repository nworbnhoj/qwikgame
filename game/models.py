from django.db import models
from player.models import Appeal, Player
from qwikgame.log import Entry
from venue.models import Venue


class Game(models.Model):
    code = models.CharField(max_length=3, primary_key=True)
    icon = models.CharField(max_length=48)
    name = models.CharField(max_length=32)

    @classmethod
    def choices(klass):
        try:
            return {game.code: game.name for game in klass.objects.all()}
        except:
            return {}

    @classmethod
    def icons(klass):
        try:
            return {game.code: game.icon for game in klass.objects.all()}
        except:
            return {}

    def __str__(self):
        return self.name


class Match(models.Model):
    date = models.DateTimeField()
    game = models.ForeignKey(Game, on_delete=models.CASCADE)
    log = models.JSONField(default=list)
    competitors = models.ManyToManyField(Player)
    venue = models.ForeignKey(Venue, on_delete=models.CASCADE)

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    def __str__(self):
        return "{} {} {}".format(self.rivals, self.date, self.venue)

    @classmethod
    def from_bid(cls, bid):
        appeal = bid.appeal
        match = cls(
            date = bid.datetime(),
            game = bid.appeal.game,
            log = bid.appeal.log.copy(),
            venue = bid.appeal.venue,
        )
        match.save()
        match.competitors.add(bid.appeal.player, bid.rival)
        match.save()
        return match

    def log_entry(self, entry):
        self.log.append(entry)
        self.save()

    # format venue_time on server, rather than in template (user timezone)
    def venue_date_str(self):
        return self._venue_time().strftime("%d %b %Y")

    # format venue_time on server, rather than in template (user timezone)
    def venue_hour_str(self):
        return self._venue_time().strftime("%H")

    def _venue_time(self):
        return self.date.astimezone(self.venue.tzinfo())
