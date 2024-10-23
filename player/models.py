import logging
import hashlib, pytz
from authenticate.models import User
from django.db import models
from pytz import datetime
from qwikgame.constants import STRENGTH, WEEK_DAYS
from qwikgame.hourbits import Hours24, Hours24x7, DAY_ALL, DAY_NONE, WEEK_NONE
from qwikgame.log import Entry
from venue.models import Place, Venue


logger = logging.getLogger(__file__)


class Player(models.Model):
    blocked = models.ManyToManyField('self', blank=True)
    email_hash = models.CharField(max_length=32, primary_key=True)
    friends = models.ManyToManyField('self', symmetrical=False, through='Friend', blank=True)
    games = models.ManyToManyField('game.Game')
    user = models.OneToOneField(User, on_delete=models.CASCADE, blank=True, null=True)

    def facet(self):
        return self.email_hash[:3].upper()

    # return a list of Appeals sorted by urgency:
    # - matching Player Filters,
    # - or by direct invitation,
    # - excluding Blocked Players. 
    def feed(self):
        filters = Filter.objects.filter(player=self, active=True)
        if filters:
            appeal_qs = Appeal.objects.none()            
            for f in filters:
                qs = Appeal.objects
                if f.game:
                    qs = qs.filter(game=f.game)
                if f.place:
                    if f.place.is_venue:
                        qs = qs.filter(venue=f.place)
                    elif f.place.is_region:
                        qs = qs.filter(venue__lat__lte=f.place.region.north)
                        qs = qs.filter(venue__lat__gte=f.place.region.south)
                        qs = qs.filter(venue__lng__lte=f.place.region.east)
                        qs = qs.filter(venue__lng__gte=f.place.region.west)
                # TODO hours intersection
                appeal_qs |= qs
        else:
            appeal_qs = Appeal.objects.all()
        # TODO include direct invites
        # TODO exclude Blocked Players
        feed = list(appeal_qs.distinct().all())
        feed = list(appeal_qs.exclude(player=self).distinct().all())
        # sort the feed by urgency
        #TODO optimise this sort (possibly at dbase order_by)
        feed.sort(key=lambda x: x.last_hour, reverse=True)
        feed.sort(key=lambda x: x.date)
        return feed

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

    def place_choices(self):
        # TODO set distinct for venues
        # TODO include venues for past matches
        filters = Filter.objects.filter(player=self, place__isnull=False).all()
        return [(f.place.placeid, f.place.name) for f in filters]

    # return a list of Appeals that this Player has
    # either made or bid-on, sorted by urgency
    def prospects(self):
        appeal_qs = Appeal.objects.filter(bid__rival=self)
        appeal_qs |= Appeal.objects.filter(player=self)
        prospects = list(appeal_qs.distinct().all())
        # sort the prospects by urgency
        #TODO optimise this sort (possibly at dbase order_by)
        prospects.sort(key=lambda x: x.last_hour, reverse=True)
        prospects.sort(key=lambda x: x.date)
        return prospects

    def reputation(self):
        return 3
        
    def save(self, *args, **kwargs):
        #if hasattr(self, 'user'):
        if self.user is not None:
            self.email_hash = hashlib.md5(self.user.email.encode()).hexdigest()
        super().save(*args, **kwargs)

    def venue_choices(self):
        # TODO set distinct for venues
        # TODO include venues for past matches
        filters = Filter.objects.filter(
            player=self,
            place__isnull=False,
            place__venue__isnull=False
            ).all()
        return [(f.place.placeid, f.place.name) for f in filters]

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
        return self.hours24.as_choices()

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

    # Compares the Appeal date and hours to the current datetime at the Venue
    # and removes past hours or deletes the Appeal when all hours have passed.
    def perish(self, dry_run=False):
        action = 'noop'
        now = self.venue.now()
        if now.date() > self.date:
            if not dry_run:
                self.delete()
            action = 'expired'
        elif now.date() == self.date:
            hour = self.venue.now().hour
            past =  [False for h in range(0, hour+1)]
            future = [True for h in range(hour+1, 24)]
            hours24 = Hours24(self.hours).intersect(Hours24(past + future))
            if hours24.is_none():
                if not dry_run:
                    self.delete()
                action = 'expired'
            elif hours24 != self.hours24:
                self.hours = hours24.as_bytes()
                if not dry_run:
                    self.save()
                action = 'perished'
        logger.debug('Appeal{} {: <9} {} @ {} {}'.format(
                ' (dry-run)' if dry_run else '',
                action,
                self.date.strftime('%a'),
                now.strftime('%a %X'),
                self.venue.name
            )
        )
        return action

    def set_hours(self, hours24):
        self.hours = hours24.as_bytes()

    def tzinfo(self):
        return self.venue.tzinfo()


class Filter(models.Model):
    active = models.BooleanField(default=True)
    game = models.ForeignKey('game.Game', null=True, on_delete=models.CASCADE)
    place = models.ForeignKey(Place, null=True, on_delete=models.CASCADE)
    player = models.ForeignKey(Player, null=True, on_delete=models.CASCADE)
    hours = models.BinaryField(default=WEEK_NONE)

    class Meta:
        constraints = [
            models.UniqueConstraint(fields=['game', 'place', 'player'], name='unique_filter')
        ]

    def __str__(self):
        hours24x7 = Hours24x7(self.hours)
        return  '{}, {}, {}'.format(
                'Any Game' if self.game is None else self.game,
                'Anywhere' if self.place is None else self.place,
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
