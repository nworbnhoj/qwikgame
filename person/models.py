import logging, hashlib, random
from datetime import datetime, timedelta
from django.core.serializers.json import DjangoJSONEncoder
from django.db import models


logger = logging.getLogger(__file__)

ICONS = [
    'fa-face-smile',
    'fa-face-smile-wink',
    'fa-face-smile-beam',
    'fa-face-rolling-eyes',
    'fa-face-meh-blank',
    'fa-face-laugh-wink',
    'fa-face-laugh-beam',
    'fa-face-laugh',
    'fa-face-laugh-wink',
    'fa-face-laugh-beam',
    'fa-face-grin-wink',
    'fa-face-grin-wide',
    'fa-face-grin-stars',
    'fa-face-grin-beam',
    'fa-face-grin',
    'fa-hand-peace',
    'fa-person-walkin',
    'fa-person-swimming',
    'fa-person-snowboarding',
    'fa-person-skiing-nordic',
    'fa-person-skiing',
    'fa-person-skating',
    'fa-person-running',
    'fa-person-hiking',
    'fa-person-falling',
    'fa-person-drowning',
    'fa-person-biking',
    'fa-user-secret',
    'fa-user',
]

def rnd_icon():
    return random.choice(ICONS)

LANGUAGE = [
    # ('bg', 'български'),
    ('en', 'English'),
    # ('es', 'Español'),
    # ('zh', '中文'),
    # ('ru', 'русский'),
    # ('fr', 'Français'),
    # ('hi', 'हिंदी'),
    # ('ar', 'اللغة العربية'),
    # ('jp', '日本語'),
]


class Alert(dict):

    def __init__(self,
        expires=None,
        pk=None,
        priority=None,
        repeats=0,
        text='',
        type='',
    ):
        dict.__init__(self,
            id=datetime.now().timestamp(),
            expires=expires,
            pk=pk,
            priority=priority,
            repeats=repeats,
            text=text,
            type=type,
        )
  

class Person(models.Model):
    alerts = models.JSONField(encoder=DjangoJSONEncoder, default=list)
    block = models.ManyToManyField('self', blank=True, symmetrical=False, through='Block')
    icon = models.CharField(max_length=32, default=rnd_icon)
    language = models.CharField(max_length=2, choices=LANGUAGE, default='en',)
    location_auto = models.BooleanField(default=False)
    name = models.CharField(max_length=32, default="my qwikname")
    notify_email = models.BooleanField(default=True)
    notify_web = models.BooleanField(default=False)
    user = models.OneToOneField('authenticate.User', on_delete=models.CASCADE)
    
    @classmethod
    def hash(cls, text):
        return hashlib.md5(text.encode()).hexdigest()

    def __str__(self):
    	return self.name

    def alert(self,
            type,
            expires=datetime.now() + timedelta(days=1),
        ):
        alert = Alert(
            expires=expires,
            type=type,
        )
        self.alerts.append(alert)
        self.save()

    def alert_del(self, id=None, type=None):
        if id:
            self.alerts = [a for a in self.alerts if a.get('id') != id]
        if type:
            self.alerts = [a for a in self.alerts if a.get('type') != type]
        self.save()
        return


    def alert_get(self, priority=None, repeats=None, type=None):
        filtered = self.alerts
        if priority:
            filtered = [a for a in filtered if a.get('priority') == priority]
        if repeats:
            filtered = [a for a in filtered if a.get('repeats') == repeats]
        if type:
            filtered = [a for a in filtered if a.get('type') == type]
        return filtered

    def alert_get_ge(self, expires=None, priority=None, repeats=None):
        filtered = self.alerts
        if expires:
            filtered = [a for a in filtered if a.get('expires') >= expires]
        if priority:
            filtered = [a for a in filtered if a.get('priority') <= priority]
        if repeats:
            filtered = [a for a in filtered if a.get('repeats') >= repeats]
        return filtered

    def alert_get_le(self, expires=None, priority=None, repeats=None):
        filtered = self.alerts
        if expires:
            filtered = [a for a in filtered if a.get('expires') <= expires]
        if priority:
            filtered = [a for a in filtered if a.get('priority') >= priority]
        if repeats:
            filtered = [a for a in filtered if a.get('repeats') <= repeats]
        return filtered

    def alert_show(self, type):
        return '' if self.alert_get(type=type) else 'hidden'

    def blocked(self):
        blocker = self.block.all()
        blockee = Person.objects.filter(block__in=[self]).all()
        return list(blocker | blockee)


class Block(models.Model):
    person = models.ForeignKey(Person, on_delete=models.CASCADE, related_name='blocker')
    blocked = models.ForeignKey(Person, on_delete=models.CASCADE, related_name='blockee')


class Social(models.Model):
    person = models.ForeignKey(Person, on_delete=models.CASCADE)
    url = models.URLField(max_length=255)

    def __str__(self):
        return self.url