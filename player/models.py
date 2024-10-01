import logging
import hashlib, pytz
from authenticate.models import User
from django.db import models
from pytz import datetime
from qwikgame.constants import STRENGTH, WEEK_DAYS
from qwikgame.hourbits import Hours24, Hours24x7, DAY_ALL, DAY_NONE, WEEK_NONE
from qwikgame.log import Entry
from venue.models import Venue


logger = logging.getLogger(__file__)


class Player(models.Model):
    blocked = models.ManyToManyField('self', blank=True)
    email_hash = models.CharField(max_length=32, primary_key=True)
    friends = models.ManyToManyField('self', symmetrical=False, through='Friend', blank=True)
    games = models.ManyToManyField('game.Game')
    user = models.OneToOneField(User, on_delete=models.CASCADE, blank=True, null=True)

    def facet(self):
        return self.email_hash[:3].upper()

    def friend_choices(self):
        choices={}
        for friend in Friend.objects.filter(player=self):
            choices[friend.rival.email_hash] = "{} ({})".format(friend.name, friend.email)
        return choices

    def name(self):
        if self.user is not None:
            return self.user.person
        else:
            return self.facet()

    def reputation(self):
        return 3
        
    def save(self, *args, **kwargs):
        #if hasattr(self, 'user'):
        if self.user is not None:
            self.email_hash = hashlib.md5(self.user.email.encode()).hexdigest()
        super().save(*args, **kwargs)

    def __str__(self):
        return self.email_hash if self.user is None else self.user.email


class Appeal(models.Model):
    date = models.DateField()
    game = models.ForeignKey('game.Game', on_delete=models.CASCADE)
    hours = models.BinaryField(default=DAY_NONE)
    log = models.JSONField(default=list)
    rivals = models.ManyToManyField('self', through='Bid')
    player = models.ForeignKey(Player, on_delete=models.CASCADE)
    venue = models.ForeignKey(Venue, on_delete=models.CASCADE)

    class Meta:
        constraints = [
            models.UniqueConstraint(fields=['date', 'game', 'player', 'venue'], name='unique_appeal')
        ]

    def __str__(self):
        return "{} {} {} {} {}".format(self.player, self.game, self.venue, self.date, Hours24(self.hours).as_bumps())

    def save(self, *args, **kwargs):
        super().save(*args, **kwargs)

    # returns an aware datetime based in the venue timezone
    def datetime(self, time=None):
        return self.venue.datetime(self.date, time)

    @property
    def hours24(self):
        return Hours24(self.hours)

    def hour_choices(self):
        return self.hours24().as_choices()

    def invite(self, rivals):
        for rival in rivals:
            try:
                invite = Bid(
                    appeal = self,
                    hours = None,
                    rival = rival,
                    strength = 'm', # TODO estimate relative strength
                )
                invite.save()
            except Exception as e:
                pass
                # TODO log exception

    # Inviting Rivals involves the following sequence:
    # - select Rival Players Available for Game at Venue
    # - exclude self.player
    # - exclude Rivals blocked by self.player
    # - exclude Rivals with no intersecting available hours
    # - add self.friends to Rivals
    # - exclude Rivals who block self.player
    def invite_rivals(self, friends):
        # TODO handle an updated appeal with changed hours
        # TODO handle an updated appeal with changed invited friends
        # TODO handle an updated appeal with changed invited rivals
        available = Available.objects.filter(
            game=self.game,
            venue=self.venue,
        ).exclude(
            player=self.player
        ).exclude (
            player__in=self.player.blocked.all()
        ).select_related('player')
        appeal_hours = Hours24x7().set_date(self.hours, self.date)
        for a in available:
            intersect = appeal_hours.intersect(a)
            if intersect.is_none():
                a.delete()
        rivals = {a.player for a in available}
        rivals |= friends
        rivals -= {Player.objects.filter(blocked=self.player).all()}
        self.invite(rivals)

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
            case 'keen':
                entry['text'] = "sent invitation".format()
            case 'appeal':
                entry['text'] = "{} at {}, {}, {}".format(
                    self.game,
                    self.venue,
                    self.venue.datetime(self.date).strftime("%b %d"),
                    self.hours24.as_str()
                )
            case _:
                entry['text'] = "unknown template"
        self.log_entry(entry)

    def set_hours(self, hours24):
        self.hours = hours24.as_bytes()

    def tzinfo(self):
        return self.venue.tzinfo()


class Filter(models.Model):
    active = models.BooleanField(default=True)
    game = models.ForeignKey('game.Game', null=True, on_delete=models.CASCADE)
    player = models.ForeignKey(Player, null=True, on_delete=models.CASCADE)
    venue = models.ForeignKey(Venue, null=True, on_delete=models.CASCADE)
    hours = models.BinaryField(default=WEEK_NONE)

    class Meta:
        constraints = [
            models.UniqueConstraint(fields=['game', 'player', 'venue'], name='unique_filter')
        ]

    def __str__(self):
        hours24x7 = Hours24x7(self.hours)
        return  '{}, {}, {}'.format(
                'Any Game' if self.game is None else self.game,
                'Any Venue' if self.venue is None else self.venue,
                'Any Time' if hours24x7.is_week_all() else hours24x7.as_str()
                )

    def hours24x7(self):
        return Hours24x7(self.hours)

    def set_hours(self, hours24x7):
        self.hours = hours24x7.as_bytes()


class Friend(models.Model):
    email = models.EmailField(max_length=255, verbose_name="email address", unique=True)
    name = models.CharField(max_length=32, blank=True)
    player = models.ForeignKey(Player, on_delete=models.CASCADE)
    rival = models.ForeignKey(Player, on_delete=models.CASCADE, related_name='usher')

    def __str__(self):
        return "{}:{}".format(self.player, self.rival)


class Bid(models.Model):
    appeal = models.ForeignKey(Appeal, on_delete=models.CASCADE)
    hours = models.BinaryField(default=WEEK_NONE, null=True)
    rival = models.ForeignKey(Player, on_delete=models.CASCADE)
    strength = models.CharField(max_length=1, choices=STRENGTH)

    def __str__(self):
        return "{} {} {} {}".format(self.rival, self.appeal.game, self.appeal.venue, Hours24(self.hours).as_bumps())

    def accepted(self):
        return self.hours is not None

    # returns the accepted datetime in venue timezone - or None otherwise
    def datetime(self):
        accepted_hour = self._hour()
        if accepted_hour is None:
            return None
        time = datetime.time(hour=accepted_hour)
        return self.appeal.datetime(time=time)

    def game(self):
        return self.appeal.game

    def _hour(self):
        if self.accepted():
            for hr, include in enumerate(self.hours24().as_bools()):
                if include:
                    return hr
        return None

    def hours24(self):
        return Hours24(self.hours)

    def hour_choices(self):
        return self.appeal.hours24x7().as_choices()

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
                    text = "accepted {} with {}".format(self.hours24().as_str(), rival.name)
                )
            case 'bid':
                person = self.rival.user.person
                entry = Entry(
                    icon = rival.icon,
                    id = self.rival.facet(),
                    klass= 'event rival',
                    name = rival.name,
                    text = "accepted {}".format(self.hours24().as_str())
                )
            case _:
                entry = Entry(text="unknown template")
        self.appeal.log_entry(entry)

    def save(self, *args, **kwargs):
        # TODO handle duplicate or partial-duplicate invitations
        # TODO notify rival
        super().save(*args, **kwargs)

    def venue(self):
        return self.appeal.venue


class Opinion(models.Model):
    date = models.DateTimeField()
    player = models.ForeignKey(Player, on_delete=models.CASCADE)
    rival = models.ForeignKey('self', on_delete=models.CASCADE)

    def __str__(self):
        return "{} {}:{} {}".format(self.date, self.player, self.rival)


class Conduct(models.Model):
    opinion = models.ForeignKey(Opinion, on_delete=models.CASCADE)
    good = models.BooleanField()

    def __str__(self):
        return "{}: {}".format(self.opinion, self.good)


class Precis(models.Model):
    game = models.ForeignKey('game.Game', on_delete=models.CASCADE)
    player = models.ForeignKey(Player, on_delete=models.CASCADE)
    text = models.CharField(max_length=512, blank=True)

    class Meta:
        verbose_name_plural = 'precis'

    def __str__(self):
        return "{}:{}".format(self.player, self.game)


class Strength(models.Model):
    opinion = models.ForeignKey(Opinion, on_delete=models.CASCADE)
    relative = models.CharField(max_length=1, choices=STRENGTH)
    weight = models.PositiveSmallIntegerField()

    def __str__(self):
        return "{} : {} {}".format(self.opinion, self.relative, self.weight)
