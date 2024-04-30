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

    def __init__(self, *args, **kwargs):
        if 'accept' in kwargs:
            invite = kwargs.pop('accept')
            kwargs['date']=invite.datetime()
            kwargs['game']=invite.game()
            kwargs['venue']=invite.venue()
        super().__init__(*args, **kwargs)

    def __str__(self):
        return "{} {} {}".format(self.rivals, self.date, self.venue)

    # format venue_time on server, rather than in template (user timezone)
    def venue_date_str(self):
        return self.venue_time().strftime("%d %b %Y")

    # format venue_time on server, rather than in template (user timezone)
    def venue_hour_str(self):
        return self.venue_time().strftime("%H")

    def venue_time(self):
        return self.date.astimezone(self.venue.tzinfo())
