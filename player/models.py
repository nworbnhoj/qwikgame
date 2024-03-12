from django.db import models

from authenticate.models import User
from game.models import Game
from venue.models import Venue


STRENGTH = [
    ('W', 'much-weaker'),
    ('w', 'weaker'),
    ('M', 'matched'),
    ('s', 'stronger'),
    ('S', 'much-stonger')
]


class Player(models.Model):
    blocked = models.ManyToManyField('self', blank=True)
    email_hash = models.CharField(max_length=32, primary_key=True)
    friends = models.ManyToManyField('self', through='Friend', blank=True)
    games = models.ManyToManyField(Game)
    icon = models.CharField(max_length=16)
    location_auto = models.BooleanField()
    notify_email = models.BooleanField()
    notify_web = models.BooleanField()
    user = models.OneToOneField(User, on_delete=models.CASCADE)

    def __str__(self):
        return self.user


class Appeal(models.Model):
    date = models.DateTimeField()
    game = models.ForeignKey(Game, on_delete=models.CASCADE)
    hours = models.IntegerField()
    rivals = models.ManyToManyField('self', through='Invite')
    player = models.ForeignKey(Player, on_delete=models.CASCADE)
    venue = models.ForeignKey(Venue, on_delete=models.CASCADE)

    def __str__(self):
        return "{} {} {} {} {}".format(self.player, self.game, self.date, self.hours, self.venue)


class Available(models.Model):
    game = models.ForeignKey(Game, on_delete=models.CASCADE)
    player = models.ForeignKey(Player, on_delete=models.CASCADE)
    venue = models.ForeignKey(Venue, on_delete=models.CASCADE)
    week = models.BinaryField()

    def __str__(self):
        return "{} {} {}".format(self.player, self.game, self.venue)


class Friend(models.Model):
    email = models.EmailField(max_length=255, verbose_name="email address", unique=True)
    rival = models.ForeignKey('self', on_delete=models.CASCADE)
    name = models.CharField(max_length=32, blank=True)
    player = models.ForeignKey(Player, on_delete=models.CASCADE)

    def __str__(self):
        return "{}:{}".format(self.player, self.rival)


class Invite(models.Model):
    appeal = models.ForeignKey(Appeal, on_delete=models.CASCADE)
    hour = models.SmallIntegerField(blank=True)
    rival = models.ForeignKey(Player, on_delete=models.CASCADE)
    strength = models.CharField(max_length=1, choices=STRENGTH)

    def __str__(self):
        return "{} {} {} {}".format(self.rival, self.appeal.game, self.hour, self.appeal.venue)


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
    text = models.CharField(max_length=512)

    class Meta:
        verbose_name_plural = 'precis'

    def __str__(self):
        return "{}:{}".format(self.player, self.game)


class Social(models.Model):
    player = models.ForeignKey(Player, on_delete=models.CASCADE)
    url = models.URLField(max_length=255)

    def __str__(self):
        return self.url


class Strength(models.Model):
    opinion = models.ForeignKey(Opinion, on_delete=models.CASCADE)
    relative = models.CharField(max_length=1, choices=STRENGTH)
    weight = models.PositiveSmallIntegerField()

    def __str__(self):
        return "{} : {} {}".format(self.opinion, self.relative, self.weight)
