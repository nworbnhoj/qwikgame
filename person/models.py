import logging, hashlib, random
from datetime import datetime, timedelta
from django.core.mail import EmailMultiAlternatives
from qwikgame.settings import FQDN
from django.template import loader
from django.core.serializers.json import DjangoJSONEncoder
from django.db import models
from webpush import send_user_notification


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

ALERT_EMAIL_DEFAULT = 'acfg'
ALERT_PUSH_DEFAULT = 'abcfgh'



class Alert(models.Model):
    MODE = {
        'E':'Email',
        'P':'Push',
    }
    TYPE = {
        'a': 'new_bid',
        'b': 'cancel_bid',
        'c': 'decline_bid',
        'f': 'new_match',
        'g': 'cancel_match',
        'h': 'chat_match',
    }
    context = models.JSONField(encoder=DjangoJSONEncoder, default=dict)
    expires = models.DateTimeField()
    mode = models.CharField(max_length=1, choices=MODE)
    person = models.ForeignKey('Person', on_delete=models.CASCADE)
    priority = models.CharField(max_length=1)
    repeats = models.PositiveSmallIntegerField(default=0)
    type = models.CharField(max_length=1, choices=TYPE)
    url = models.CharField(max_length=256)

    @property
    def ttl(self):
        tzinfo = self.expires.tzinfo
        now = datetime.now(tzinfo)
        remaining =  self.expires - now
        return max(0, int(remaining.total_seconds()))

    def dispatch(self):
        match self.mode:
            case 'E':
                return self._email()
            case 'P':
                return self._push()
            case _:
                logger.warn(f'unimplemented Alert mode: {self.mode}')

    def _push(self):
        logger.info(f'Alert._push(): {self.pk}')
        try:
            alert_type = Alert.TYPE[self.type]
            head_template_name = f'person/{alert_type}_alert_notify_head.txt',
            body_template_name = f'person/{alert_type}_alert_notify_body.txt',
            payload = {

                'body': loader.render_to_string(body_template_name, self.context),
                'head': loader.render_to_string(head_template_name, self.context),
                'icon': 'icon',
                'url': self.url,
            }
            send_user_notification(
                payload = payload,
                ttl = self.ttl,
                user = self.person.user,
            )
        except Exception:
            logger.exception( f'Failed to send Alert Notification: {self}' )
        return True

    def _email(self):
        logger.info(f'Alert.send_mail(): {self.pk}')
        try:
            alert_type = Alert.TYPE[self.type]
            subject_template_name = f'person/{alert_type}_alert_email_subject.txt',
            email_template_name = f'person/{alert_type}_alert_email_text.html',
            html_email_template_name = f'person/{alert_type}_alert_email_html.html',
            subject = loader.render_to_string(subject_template_name, self.context)
            # Email subject *must not* contain newlines
            subject = "".join(subject.splitlines())
            email_message = EmailMultiAlternatives(
                subject,
                loader.render_to_string(email_template_name, self.context),
                'accounts@qwikgame.org',
                [self.context.get('to_email')]
            )
            # if html_email_template_name is not None:
            #     logger.info(html_email_template_name)
            #     html_email = loader.render_to_string(html_email_template_name, self.context)
            #     email_message.attach_alternative(html_email, "text/html")
            return email_message.send()
        except Exception:
            logger.exception( f'Failed to send Alert email: {self}' )
            return False
        return True


class Person(models.Model):
    block = models.ManyToManyField('self', blank=True, symmetrical=False, through='Block')
    icon = models.CharField(max_length=32, default=rnd_icon)
    language = models.CharField(max_length=2, choices=LANGUAGE, default='en',)
    location_auto = models.BooleanField(default=False)
    name = models.CharField(max_length=32, default="my qwikname")
    notify_email = models.CharField(max_length=64, default='acfg')
    notify_push = models.CharField(max_length=64, default='')
    user = models.OneToOneField('authenticate.User', on_delete=models.CASCADE)
    
    @classmethod
    def hash(cls, text):
        return hashlib.md5(text.encode()).hexdigest()

    def __str__(self):
    	return self.qwikname

    def alert(self,
            type,
            expires=datetime.now() + timedelta(days=1),
            context={},
            url=FQDN,
        ):
        alert = Alert (
            context = context,
            expires = expires,
            mode = 'E',
            person = self,
            priority = 'A',
            repeats = 0,
            type = type,
            url=url,
        )
        if type in  self.notify_email:
            alert.pk = None
            alert.mode = 'E'
            alert.context['to_email'] = self.user.email
            if not alert.dispatch():
                alert.save()
        if type in  self.notify_push:
            alert.pk = None
            alert.mode = 'P'
            if not alert.dispatch():
                alert.save()

    def alert_del(self, id=None, type=None):
        Alert.objects.filter(id=id, person=self, type=type).delete()
        return

    def alert_get(self, priority=None, repeats=None, type=None):
        return Alert.objects.filter(person=self, priority=priority, repeats=repeats, type=type)

    def alert_get_ge(self, expires=None, priority=None, repeats=None):
        return Alert.objects.filter(person=self, expires_ge=expires, priority_le=priority, repeats_ge=repeats)
    
    def alert_get_le(self, expires=None, priority=None, repeats=None):
        return Alert.objects.filter(person=self, expires_le=expires, priority_ge=priority, repeats_le=repeats)

    def alert_show(self, type):
        return '' if self.alert_get(type=type) else 'hidden'

    def block_rival(self, rival):
        self.block.add(rival)

    def blocked(self):
        blocker = self.block.all()
        blockee = Person.objects.filter(block__in=[self]).all()
        return list(blocker | blockee)

    def facet(self):
        return Person.hash(self.user.email)[:3].upper()

    @property
    def qwikname(self):
        if self.name:
            return self.name
        return self.facet()


class Block(models.Model):
    person = models.ForeignKey(Person, on_delete=models.CASCADE, related_name='blocker')
    blocked = models.ForeignKey(Person, on_delete=models.CASCADE, related_name='blockee')


class Social(models.Model):
    person = models.ForeignKey(Person, on_delete=models.CASCADE)
    url = models.URLField(max_length=255)

    class Meta:
        constraints = [
            models.UniqueConstraint(fields=['person', 'url'], name='unique_social')
        ]

    def __str__(self):
        return self.url