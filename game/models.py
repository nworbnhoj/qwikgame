import logging
from django.db import models
from player.models import Appeal, Player
from qwikgame.constants import DELAY_MATCH_PERISH_CHAT, DELAY_REVIEW_PERISH, MATCH_STATUS
from qwikgame.log import Entry
from venue.models import Venue

logger = logging.getLogger(__file__)


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
    status = models.CharField(max_length=1, choices=MATCH_STATUS, default='A')
    competitors = models.ManyToManyField(Player)
    venue = models.ForeignKey(Venue, on_delete=models.CASCADE)

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    def __str__(self):
        names = [player.name() for player in self.competitors.all()]
        return f'{self.game} {names} {self.date.strftime("%Y-%m-%d %H")}h {self.venue}'

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

    def log_clear(self):
        self.log = []
        self.save()

    def log_entry(self, entry):
        self.log.append(entry)
        self.save()

    def log_event(self, template):
        match template:
            case 'scheduled':
                player = self.competitors.first()
                person = player.user.person
                entry = Entry(
                    icon = person.icon,
                    id = player.facet(),
                    klass= 'scheduled',
                    name = person.name,
                    text = f'scheduled'
                )
            case 'match_perished':
                entry = Entry(
                    icon = '',
                    id = '',
                    klass='system',
                    name = 'system',
                    text=f'match chat perished'
                )
            case _:
                logger.warn(f'unknown template: {template}')
        self.log_entry(entry)

    def perish(self, dry_run=False):
        now = self.venue.now()
        if now > (self.date + DELAY_MATCH_PERISH_CHAT):
            dry = 'dry_run' if dry_run else ''
            if not dry_run:
                self.log_clear()
                self.log_event('match_perished')
                self.save()
            logger.info(f'match perished {dry}: {self}')
            return 'chat'
        return 'noop'

    # format venue_time on server, rather than in template (user timezone)
    def venue_date_str(self):
        return self._venue_time().strftime("%d %b %Y")

    # format venue_time on server, rather than in template (user timezone)
    def venue_hour_str(self):
        return self._venue_time().strftime("%H")

    def _venue_time(self):
        return self.date.astimezone(self.venue.tzinfo())


class Review(models.Model):
    match = models.ForeignKey(Match, on_delete=models.CASCADE)
    player = models.ForeignKey(Player, on_delete=models.CASCADE, related_name='reviewer')
    rival = models.ForeignKey(Player, on_delete=models.CASCADE, related_name='reviewee')

    def __str__(self):
        return f"{self.player}: {self.rival} {self.match}"

    def log_event(self, template):
        logger.warn('log_event()')
        match template:
            case 'review':
                player = self.player
                person = self.player.user.person
                entry = Entry(
                    icon = person.icon,
                    id = self.player.facet(),
                    klass= 'reviewed',
                    name = person.name,
                    text = f'reviewed {self.rival}'
                )
            case _:
                logger.warn(f'unknown template: {template}')
        self.match.log_entry(entry)

    def perish(self, dry_run=False):
        action = 'noop'
        now = self.match.venue.now()
        if now.date() > self.match.date + DELAY_REVIEW_PERISH:
            if not dry_run:
                self.delete()
            action = 'expired'
        return action
