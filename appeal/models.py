import datetime, logging
from django.db import models
from django.utils.timezone import now
from game.models import Match
from player.models import Friend, Player, Strength
from qwikgame.hourbits import Hours24, Hours24x7, DAY_ALL, DAY_NONE, DAY_QWIK, WEEK_NONE, WEEK_QWIK
from qwikgame.log import Entry


logger = logging.getLogger(__file__)


class Appeal(models.Model):

    STATUS = {
        'A': 'active',
        'C': 'cancelled',
        'D': 'dormant',
        'X': 'expired',
    }

    created = models.DateTimeField(default=now, editable=False)
    date = models.DateField()
    game = models.ForeignKey('game.Game', on_delete=models.CASCADE)
    hours = models.BinaryField(default=DAY_NONE)
    invitees = models.ManyToManyField(Friend, related_name='invitees')
    log = models.JSONField(default=list)
    meta = models.JSONField(default=dict)
    rivals = models.ManyToManyField(Player, related_name='rivals', through='Bid')
    player = models.ForeignKey(Player, on_delete=models.CASCADE)
    status = models.CharField(max_length=1, choices=STATUS, default='A')
    venue = models.ForeignKey('venue.Venue', on_delete=models.CASCADE)

    class Meta:
        constraints = [
            models.UniqueConstraint(fields=['date', 'game', 'player', 'venue'], name='unique_appeal')
        ]

    def __str__(self):
        return "{} {} {} {}".format(self.player, self.game, self.venue, self.date)

    # Alert player and rivals of change to this Appeal
    # ( optionally omitting the Player causing the change )
    def alert(self, omit_player):
        recipients = list(self.rivals.all())
        if omit_player != self.player:
            recipients.append(self.player)
            recipients.remove(omit_player)
        for pk in recipients:
            player=Player.objects.filter(pk=pk).first()
            if player:
                player.user.person.alert(type='appeal', expires=self.midnight())

    def accept(self, bid_pk):
        try:
            bid = Bid.objects.get(pk=bid_pk)
            appeal = bid.appeal
            appeal.status = 'D'
            appeal.alert(self.player)
            bid.log_event('accept')
            match = Match.from_bid(bid)
            bid.delete()
            match.alert(self.player)
            match.log_event('scheduled')
            match.clear_conflicts(self)
            return match
        except:
            logger.exception(f'failed to accept Bid: {bid_pk}')
        return None

    def cancel(self):
        logger.info(f'Cancelling Appeal: {self}')
        if Bid.objects.filter(appeal=self).count() == 0:
            self.delete()
        else:
            self.status = 'C'
            self.alert(self.player)
            self.meta['seen'] = [self.player.pk]
            self.log_event('cancelled')
        try:
            from api.models import Mark
            Mark.objects.get(game=self.game, place=self.venue).save()
        except:
            logger.exception(f'failed to update Mark for {self.game} at {self.venue}')
        if self.status == 'C':
            return self
        return

    def created_str(self):
        return self.created.strftime("%Y-%m-%d %H:%M:%S%z")

    def save(self, *args, **kwargs):
        super().save(*args, **kwargs)

    # returns an aware datetime based in the venue timezone
    def datetime(self, time=datetime.time(hour=0)):
        return self.venue.datetime(self.date, time)

    @property
    def hours24(self):
        return Hours24(self.hours)

    def hour_choices(self):
        return self.hours24.as_choices()

    def hour_dips(self):
        return self.hours24.to_dips

    def hour_list(self):
        return self.hours24.as_list()

    def hour_withdraw(self, hour):
        hours24 = self.hours24
        if hours24.is_hour(hour):
            self.hours = hours24.unset_hour(hour).as_bytes()
            entry = Entry(
                icon = self.player.icon,
                id = self.player.facet(),
                klass= 'event',
                name = self.player.name(),
                text = f'withdrew {hour}h'
            )
            self.log_entry(entry)
            hour_bytes = Hours24().set_hour(hour).as_bytes()
            for bid in Bid.objects.filter(appeal=self, hours=hour_bytes):
                bid.withdraw()
            self.meta['seen'] = []

    @property
    def is_open(self):
        return self.invitees.count() == 0

    @property
    def last_hour(self):
        return self.hours24.last_hour()

    def log_entry(self, entry):
        self.log.append(entry)
        self.save()

    def log_event(self, template):
        entry = Entry(
            icon = self.player.user.person.icon,
            id = self.player.facet(),
            klass= 'event',
            name=self.player.user.person.name,
        )
        match template:
            case 'appeal':
                entry['text'] = "Proposed {} at {}, {}, {}".format(
                    self.game,
                    self.venue,
                    self.venue.datetime(self.date).strftime("%b %d"),
                    self.hours24.as_str()
                )
                if not self.is_open:
                    friends = ', '.join(friend.name() for friend in self.invitees.all())
                    entry['text'] += f" with {friends}"
            case 'cancelled':
                entry['text'] = "Cancelled Invitation"
            case _:
                logger.warn(f'unknown template: {template}')
        self.log_entry(entry)

    # returns an aware datetime for midnight on the day of this Appeal
    def midnight(self):
        return self.datetime(datetime.time(hour=23, minute=59, second=59))

    # Deletes the Appeal at the end of the day.
    def perish(self, dry_run=False):
        action = 'noop'
        now = self.venue.now()
        if now.date() > self.date:
            if not dry_run:
                self.delete()
            action = 'deleted'
        if now.date() == self.date and now.hour >= self.hours24.last_hour():
            if not dry_run:
                self.status = 'X'
                self.save()
            action = 'expired'
        logger.debug('Appeal{} {: <9} {} @ {} {}'.format(
                ' (dry-run)' if dry_run else '',
                action,
                self.date.strftime('%a'),
                now.strftime('%a %X'),
                self.venue.name
            )
        )
        return action

    def mark_seen(self, player_pks=[]):
        seen = set(self.meta.get('seen', []))
        seen.update(player_pks)
        self.meta['seen'] = list(seen)
        return self

    def set_hours(self, hours24):
        self.hours = hours24.as_bytes()

    @property
    def tzinfo(self):
        return self.venue.tzinfo


class Bid(models.Model):
    appeal = models.ForeignKey(Appeal, on_delete=models.CASCADE)
    hours = models.BinaryField(default=WEEK_NONE, null=True)
    rival = models.ForeignKey(Player, on_delete=models.CASCADE)
    strength = models.CharField(max_length=1, choices=Strength.SCALEZ, default='m')
    str_conf = models.CharField(max_length=1, choices=Strength.CONFIDENCE, default='z')

    def __str__(self):
        return "{} {} {}".format(self.rival, self.appeal.game, self.appeal.venue)

    def accepted(self):
        return self.hours is not None

    # returns the accepted datetime in venue timezone - or None otherwise
    @property
    def datetime(self):
        accepted_hour = self._hour
        if accepted_hour is None:
            return None
        time = datetime.time(hour=accepted_hour)
        return self.appeal.datetime(time=time)

    def game(self):
        return self.appeal.game

    @property
    def _hour(self):
        if self.accepted():
            return self.hours24().last_hour()
        return None

    def hours24(self):
        return Hours24(self.hours)

    def hour_choices(self):
        return self.appeal.hours24x7().as_choices()

    def hour_dips(self):
        return self.hours24.to_dips

    def log_event(self, template):
        rival = self.rival.user.person
        match template:
            case 'accept':
                player = self.appeal.player
                person = player.user.person
                entry = Entry(
                    icon = person.icon,
                    id = player.facet(),
                    klass= 'event',
                    name = person.name,
                    pk = self.pk,
                    text = f'Confirmed for {self.hours24().as_str()} with {rival.name}'
                )
            case 'bid':
                person = self.rival.user.person
                entry = Entry(
                    icon = person.icon,
                    id = self.rival.facet(),
                    klass= 'event rival',
                    name = person.name,
                    pk = self.pk,
                    text = f'Accepted for {self.hours24().as_str()}'
                )
            case 'decline':
                player = self.appeal.player
                person = player.user.person
                entry = Entry(
                    icon = person.icon,
                    id = player.facet(),
                    klass= 'event',
                    name = person.name,
                    pk = self.pk,
                    text = f'declined {self.hours24().as_str()} with {rival.name}'
                )
            case 'expired':
                person = self.rival.user.person
                entry = Entry(
                    icon = person.icon,
                    id = self.rival.facet(),
                    klass= 'event rival',
                    name = person.name,
                    pk = self.pk,
                    text = f'bid expired'
                )
            case 'withdraw':
                person = self.rival.user.person
                entry = Entry(
                    icon = rival.icon,
                    id = self.rival.facet(),
                    klass= 'event rival',
                    name = rival.name,
                    pk = self.pk,
                    text = f'withdrew {self.hours24().as_str()}'
                )
            case _:
                logger.warn(f'unknown template: {template}')
        self.appeal.log_entry(entry)

    def withdraw(self):
        self.log_event('withdraw')
        self.appeal.meta['seen'] = []
        self.delete()


    # Deletes the Bid if the Bid hours has passed.
    def perish(self, dry_run=False):
        action = 'noop'
        now = self.venue().now()
        if now > self.datetime:
            if not dry_run:
                self.log_event('expired')
                self.delete()
            action = 'expired'
        logger.debug('Bid{} {: <9} {} @ {} {}'.format(
                ' (dry-run)' if dry_run else '',
                action,
                self.datetime.strftime('%a %X'),
                now.strftime('%a %X'),
                self.venue().name
            )
        )
        return action


    def save(self, *args, **kwargs):
        # TODO handle duplicate or partial-duplicate invitations
        # TODO notify rival
        super().save(*args, **kwargs)

    def strength_str(self):
        return Strength.description(self.strength, self.str_conf)

    def venue(self):
        return self.appeal.venue
