from django.utils.timezone import now

class Entry(dict):

    def __init__(self, id, hash=None, klass=None, name=None, pk=None, text=None):
        dict.__init__(self, id=id, created=now().strftime("%Y-%m-%d %H:%M:%S%z"), hash=hash, klass=klass, name=name, pk=pk, text=text)

    @property
    def player(self):
        if id in self:
            return player.models.Player.objects.filter(pk=self['id']).first();

    def rename(self, player):
        rival = self.player
        if rival:
            self['name'] = player.name_rival(rival);

