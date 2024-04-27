from django.db import models


class Game(models.Model):
    code = models.CharField(max_length=3, primary_key=True)
    icon = models.CharField(max_length=48)
    name = models.CharField(max_length=32)

    def __str__(self):
        return self.name

    def choices():
        return {game.code: game.name for game in Game.objects.all()}

    def icons():
        return {game.code: game.icon for game in Game.objects.all()}


class Match(models.Model):
    date = models.DateTimeField()
    game = models.ForeignKey(Game, on_delete=models.CASCADE)
    rivals = models.ManyToManyField('player.Player')
    venue = models.ForeignKey('venue.Venue', on_delete=models.CASCADE)

    def __str__(self):
        return "{} {} {}".format(self.rivals, self.date, self.venue)

