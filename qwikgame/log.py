from django.utils.timezone import now

class Entry(dict):

    def __init__(self, id, icon=None, klass=None, name=None, pk=None, text=None):
        dict.__init__(self, id=id, created=now().strftime("%Y-%m-%d %H:%M:%S%z"), icon=icon, klass=klass, name=name, pk=pk, text=text)
