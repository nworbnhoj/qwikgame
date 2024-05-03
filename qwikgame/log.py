class Entry(dict):

    def __init__(self, id, icon=None, klass=None, name=None, text=None):
        dict.__init__(self, id=id, icon=icon, klass=klass, name=name, text=text)