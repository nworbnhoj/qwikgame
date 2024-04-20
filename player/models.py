import hashlib
from authenticate.models import User
from django.db import models
from game.models import Game
from qwikgame.constants import ENDIAN
from qwikgame.utils import bytes_to_bumps, int_to_bools24
from venue.models import Venue

STRENGTH = [
    ('W', 'much-weaker'),
    ('w', 'weaker'),
    ('m', 'matched'),
    ('s', 'stronger'),
    ('S', 'much-stonger')
]

WEEK_DAYS = [
    ('MONDAY'),
    ('TUESDAY'),
    ('WEDNESDAY'),
    ('THURSDAY'),
    ('FRIDAY'),
    ('SATURDAY'),
    ('SUNDAY'),
]

class Player(models.Model):
    blocked = models.ManyToManyField('self', blank=True)
    email_hash = models.CharField(max_length=32, primary_key=True)
    friends = models.ManyToManyField('self', symmetrical=False, through='Friend', blank=True)
    games = models.ManyToManyField(Game)
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
    game = models.ForeignKey(Game, on_delete=models.CASCADE)
    hours = models.BinaryField(default=b'\x00\x00\x00')
    rivals = models.ManyToManyField('self', through='Invite')
    player = models.ForeignKey(Player, on_delete=models.CASCADE)
    venue = models.ForeignKey(Venue, on_delete=models.CASCADE)

    class Meta:
        constraints = [
            models.UniqueConstraint(fields=['date', 'game', 'player', 'venue'], name='unique_appeal')
        ]

    def __str__(self):
        return "{} {} {} {} {}".format(self.player, self.game, self.venue, self.date, bytes_to_bumps(self.hours))

    def save(self, *args, **kwargs):
        super().save(*args, **kwargs)

    def invite_friends(self, friends):
        for friend in friends:
            invite = Invite(
                appeal = self,
                hours = self.hours,
                rival = friend,
                strength = 'm', # TODO estimate relative strength
            )
            invite.save()

    def invite_rivals(self, friends):
        # TODO handle an updated appeal with changed hours
        # TODO handle an updated appeal with changed invited friends
        # TODO handle an updated appeal with changed invited rivals
        self.invite_friends(friends)
        # TODO invite other suitable qwikgame Rivals


class Available(models.Model):
    game = models.ForeignKey(Game, on_delete=models.CASCADE)
    player = models.ForeignKey(Player, on_delete=models.CASCADE)
    venue = models.ForeignKey(Venue, on_delete=models.CASCADE)
    hours = models.BinaryField()

    class Meta:
        constraints = [
            models.UniqueConstraint(fields=['game', 'player', 'venue'], name='unique_availability')
        ]

    def __str__(self):
        return "{} {} {}".format(self.player, self.game, self.venue)

    def get_hours_day(self, day):
        offset = 3 * day
        three_bytes = self.hours[offset: offset+3]
        return int_to_bools24(int.from_bytes(three_bytes, ENDIAN))

    def get_hours_week(self):
        return [self.get_hours_day(day) for day in range(len(WEEK_DAYS))]

class Friend(models.Model):
    email = models.EmailField(max_length=255, verbose_name="email address", unique=True)
    name = models.CharField(max_length=32, blank=True)
    player = models.ForeignKey(Player, on_delete=models.CASCADE)
    rival = models.ForeignKey(Player, on_delete=models.CASCADE, related_name='usher')

    def __str__(self):
        return "{}:{}".format(self.player, self.rival)


class Invite(models.Model):
    appeal = models.ForeignKey(Appeal, on_delete=models.CASCADE)
    hours = models.BinaryField()
    rival = models.ForeignKey(Player, on_delete=models.CASCADE)
    strength = models.CharField(max_length=1, choices=STRENGTH)

    def __str__(self):
        return "{} {} {} {}".format(self.rival, self.appeal.game, self.appeal.venue, bytes_to_bumps(self.hours))

    def save(self, *args, **kwargs):
        # TODO handle duplicate or partial-duplicate invitations
        # TODO notify rival
        super().save(*args, **kwargs)


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
    game = models.ForeignKey(Game, on_delete=models.CASCADE)
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
