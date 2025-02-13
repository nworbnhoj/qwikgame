import datetime, logging
from django.db import models
from qwikgame.constants import DELAY_MATCH_PERISH_CHAT, DELAY_REVIEW_PERISH
from qwikgame.log import Entry

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

    STATUS = {
        'A': 'active',
        'C': 'complete',
        'X': 'cancelled',
    }

    date = models.DateTimeField()
    game = models.ForeignKey(Game, on_delete=models.CASCADE)
    log = models.JSONField(default=list)
    meta = models.JSONField(default=dict)
    status = models.CharField(max_length=1, choices=STATUS, default='A')
    competitors = models.ManyToManyField("player.Player")
    venue = models.ForeignKey('venue.Venue', on_delete=models.CASCADE)

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    def __str__(self):
        names = [player.qwikname for player in self.competitors.all()]
        return f'{self.game} {names} {self.datetime_str} {self.venue}'

    @classmethod
    def from_bid(cls, bid):
        appeal = bid.appeal
        match = cls(
            date = bid.datetime,
            game = bid.appeal.game,
            log = bid.appeal.log.copy(),
            venue = bid.appeal.venue,
        )
        match.save()
        match.competitors.add(bid.appeal.player, bid.rival)
        match.save()
        return match

    def alert(self, player):
        from player.models import Player
        recipients = list(self.competitors.all())
        recipients.remove(player)
        for recipient in recipients:
            player = Player.objects.filter(pk=recipient.pk).first()
            if player:
                player.alert(
                    type='match',
                    expires=self.date + datetime.timedelta(days=1) 
                )

    def clear_conflicts(self, scheduled_appeal):
        from appeal.models import Bid, Appeal
        for bid in Bid.objects.filter(
                appeal__date=self.date,
                rival__in=self.competitors.all(),
            ).exclude(appeal=scheduled_appeal):
            bid.withdraw()
        for appeal in Appeal.objects.filter(
                player__in=self.competitors.all(),
                date=self.date.date()
            ).exclude(pk=scheduled_appeal.pk):
            appeal.hour_withdraw(self.date.hour)

    def competitor_names(self):
        return [player.qwikname for player in self.competitors.all()]

    @property
    def date_str(self):
        return self.datetime_aware.strftime('%d %b %Y')

    @property
    def datetime_aware(self):
        return self.date.astimezone(self.venue.tzinfo)
    
    @property
    def datetime_str(self):
        return self.datetime_aware.strftime('%d %b %Y, %Hh')

    @property
    def hour_str(self):
        return self.datetime_aware.strftime('%Hh')

    def icons(self):
        from player.models import Player
        pks = list(self.competitors.values_list('pk', flat=True))
        players = Player.objects.filter(pk__in=pks)
        return {p.pk:p.icon for p in players}


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
                    name = person.qwikname,
                    text = f'scheduled'
                )
            case 'match_perished':
                entry = Entry(
                    icon = 'fa-face-smile',
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
            logger.debug(f'match perished {dry}: {self}')
            return 'chat'
        return 'noop'

    def mark_seen(self, player_pks=[]):
        seen = set(self.meta.get('seen', []))
        seen.update(player_pks)
        self.meta['seen'] = list(seen)
        return self

    def rivals(self, player):
        competitors = list(self.competitors.values_list('pk', flat=True))
        return competitors.remove(player.pk)

    # format venue_time on server, rather than in template (user timezone)
    def venue_date_str(self):
        return self._venue_time().strftime("%d %b %Y")

    # format venue_time on server, rather than in template (user timezone)
    def venue_hour_str(self):
        return self._venue_time().strftime("%H")

    def _venue_time(self):
        return self.date.astimezone(self.venue.tzinfo)


class Review(models.Model):
    match = models.ForeignKey(Match, on_delete=models.CASCADE)
    meta = models.JSONField(default=dict)
    player = models.ForeignKey("player.Player", on_delete=models.CASCADE, related_name='reviewer')
    rival = models.ForeignKey("player.Player", on_delete=models.CASCADE, related_name='reviewee')

    def __str__(self):
        return f"{self.player}: {self.rival} {self.match}"

    def log_event(self, template):
        match template:
            case 'review':
                player = self.player
                person = self.player.user.person
                entry = Entry(
                    icon = person.icon,
                    id = self.player.facet(),
                    klass= 'reviewed',
                    name = person.qwikname,
                    text = f'reviewed the Match'
                )
            case _:
                logger.warn(f'unknown template: {template}')
        self.match.log_entry(entry)

    def mark_seen(self, player_pks=[]):
        seen = set(self.meta.get('seen', []))
        seen.update(player_pks)
        self.meta['seen'] = list(seen)
        return self

    def perish(self, dry_run=False):
        action = 'noop'
        now = self.match.venue.now()
        if now > self.match.date + DELAY_REVIEW_PERISH:
            if not dry_run:
                self.delete()
            action = 'expired'
        return action
