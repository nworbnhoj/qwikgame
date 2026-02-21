import logging, hashlib, random
from datetime import datetime, timedelta
from django.core.mail import EmailMultiAlternatives, get_connection
from qwikgame.settings import FQDN, EMAIL_ALERT_NAME, EMAIL_ALERT_PASSWORD, EMAIL_ALERT_USER, EMAIL_SMTP_TIMEOUT
from django.template import loader
from django.core.serializers.json import DjangoJSONEncoder
from django.db import models
from pywebpush import WebPushException
from webpush import send_user_notification


logger = logging.getLogger(__file__)


def rnd_icon():
    return 'fa-face-smile'

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

ALERT_EMAIL_DEFAULT = 'bkmpq'
ALERT_PUSH_DEFAULT = 'bcklmpqr'


class AlertEmail(EmailMultiAlternatives):

    _open_connection = None
    _timeout_connection = datetime.now()

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.get_connection()
        self.from_email = "{}<{}>".format(EMAIL_ALERT_NAME, EMAIL_ALERT_USER);

    def get_connection(self, fail_silently=False):
        if AlertEmail._open_connection:
            self.connection = AlertEmail._open_connection
        else:
            self.connection = get_connection(
                username = EMAIL_ALERT_USER,
                password = EMAIL_ALERT_PASSWORD,
            )
            if self.connection.open():
                AlertEmail._open_connection = self.connection
                logger.info(f'SMTP connection opened')
            else:
                logger.warn(f'SMTP connection open failed')
        AlertEmail.prolong_connection()
        return self.connection

    @classmethod
    def prolong_connection(klass):
        extention = timedelta(seconds=EMAIL_SMTP_TIMEOUT)
        AlertEmail._timeout_connection = datetime.now() + extention

    @classmethod
    @property
    def timeout():
        connection = AlertEmail._open_connection
        if connection and now() > AlertEmail._timeout_connection:
            AlertEmail._open_connection = None
            connection.close()
            logger.info(f'SMTP connection closed')
            return true
        return false


class Alert(models.Model):
    MODE = {
        'E':'Email',
        'P':'Push',
        'U':'UI',
    }
    TYPE = {
        'a': 'woo_invite',
        'b': 'rival_invite',
        'c': 'filter_invite',
        'k': 'new_bid',
        'l': 'cancel_bid',
        'm': 'decline_bid',
        'p': 'new_match',
        'q': 'cancel_match',
        'r': 'chat_match',
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

    @classmethod
    def str(self, on=True, keys='', type='_', route='all'):
        if on:
            list = [v.split('_')[0] for k,v in Alert.TYPE.items() if  k in keys and type in v ]
        else:
            list = [v.split('_')[0] for k,v in Alert.TYPE.items() if k not in keys and type in v ]
        return ' '.join(list)

    def dispatch(self):
        self.context['domain'] = FQDN
        match self.mode:
            case 'E':
                return self._email()
            case 'P':
                return self._push()
            case _:
                logger.warn(f'unimplemented Alert mode: {self.mode}')
        return False;

    def _push(self):
        logger.info(f'Alert._push(): {self.person.hash}')
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
        except WebPushException as e:
            eol = e.message.find('\n')
            logger.warn(f'Player {self.person.pk}: {e.message[:eol]}')
            return False;
        except Exception:
            logger.exception( f'Failed to send Alert Notification: {self}' )
            return False;
        return True

    def _email(self):
        logger.info(f'Alert.send_mail(): {self.person.hash}')
        try:
            alert_type = Alert.TYPE[self.type]
            subject_template_name = f'person/{alert_type}_alert_email_subject.txt',
            email_template_name = f'person/{alert_type}_alert_email_text.html',
            html_email_template_name = f'person/{alert_type}_alert_email_html.html',
            subject = loader.render_to_string(subject_template_name, self.context)
            # Email subject *must not* contain newlines
            subject = "".join(subject.splitlines())
            email_message = AlertEmail(
                subject,
                loader.render_to_string(email_template_name, self.context),
                EMAIL_ALERT_USER,
                [self.context.get('to_email')]
            )
            # if html_email_template_name is not None:
            #     logger.info(html_email_template_name)
            #     html_email = loader.render_to_string(html_email_template_name, self.context)
            #     email_message.attach_alternative(html_email, "text/html")
            return email_message.send() > 0
        except Exception:
            logger.exception( f'Failed to send Alert email: {self}' )
        return False

    


class Person(models.Model):
    block = models.ManyToManyField('self', blank=True, symmetrical=False, through='Block')
    icon = models.CharField(max_length=32, default=rnd_icon)
    language = models.CharField(max_length=2, choices=LANGUAGE, default='en',)
    location_auto = models.BooleanField(default=False)
    name = models.CharField(max_length=32, default="my qwikname")
    notify_email = models.CharField(max_length=64, default='kmpq')
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
            url='',
        ):
        alert = Alert (
            # context = context when able to serialize context
            context = {},
            expires = expires,
            mode = 'U',
            person = self,
            priority = 'A',
            repeats = 0,
            type = type,
            url=url,
        )
        # save UI Alert
        alert.save()
        # remove when able to serialize context
        alert.context = context
        if type in  self.notify_email:
            alert.pk = None
            alert.mode = 'E'
            alert.context['to_email'] = self.user.email
            if not alert.dispatch():
        #        alert.save()
                logger.warn("Alert discarded - TODO serialize, save & replay unsent Alerts");
        if type in  self.notify_push:
            alert.pk = None
            alert.mode = 'P'
            if not alert.dispatch():
        #        alert.save()
                logger.warn("Alert discarded - TODO serialize, save & replay unsent Alerts");

    def alert_del(self, id=None, type=None):
        Alert.objects.filter(id=id, person=self, type=type).delete()
        qs = Alert.objects.filter(person=self)
        if id:
            qs = qs.filter(id=id)
        if type:
            qs = qs.filter(type__in=type)
        qs.delete()

    def alert_get(self, priority=None, repeats=None, type=None):
        return Alert.objects.filter(person=self, priority=priority, repeats=repeats, type=type)

    def alert_get_ge(self, expires=None, priority=None, repeats=None):
        return Alert.objects.filter(person=self, expires_ge=expires, priority_le=priority, repeats_ge=repeats)
    
    def alert_get_le(self, expires=None, priority=None, repeats=None):
        return Alert.objects.filter(person=self, expires_le=expires, priority_ge=priority, repeats_le=repeats)

    def alert_show(self, type=None):
        return '' if type and Alert.objects.filter(person=self, type__in=type) else 'hidden'

    def block_rival(self, rival):
        self.block.add(rival)

    def blocked(self):
        blocker = self.block.all()
        blockee = Person.objects.filter(block__in=[self]).all()
        return list(blocker | blockee)

    def facet(self):
        return Person.hash(self.user.email)[:3].upper()

    def alert_str(self, on=True, type='_', route='all'):
        keys = ''
        match route:
            case 'all':
                keys = self.notify_email + self.notify_push
            case 'email':
                keys = self.notify_email
            case 'push':
                keys = self.notify_push
        return Alert.str(on, keys, type, route)

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