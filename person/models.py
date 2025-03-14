import logging, hashlib, random
from datetime import datetime, timedelta
from django.core.mail import EmailMultiAlternatives
from django.template import loader
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
    	return self.qwikname

    def alert(self,
            type,
            expires=datetime.now() + timedelta(days=1),
            context={},
        ):
        if self.notify_email:
            match type:
                case 'bid_cancel':
                    pass
                    # self.send_mail(
                    #     'appeal/cancel_bid_alert_email_subject.txt',
                    #     'appeal/cancel_bid_alert_email_text.html',
                    #     context,
                    #     'appeal/cancel_bid_alert_email_html.html',
                    # )
                case 'bid_decline':
                    self.send_mail(
                        'appeal/decline_bid_alert_email_subject.txt',
                        'appeal/decline_bid_alert_email_text.html',
                        context,
                        'appeal/decline_bid_alert_email_html.html',
                    )
                case 'bid_new':
                    self.send_mail(
                        'appeal/new_bid_alert_email_subject.txt',
                        'appeal/new_bid_alert_email_text.html',
                        context,
                        'appeal/new_bid_alert_email_html.html',
                    )
                case 'match_chat':
                    pass
                    # self.send_mail(
                    #     'game/chat_match_alert_email_subject.txt',
                    #     'game/chat_match_alert_email_text.html',
                    #     context,
                    #     'game/chat_match_alert_email_html.html',
                    # )
                case 'match_new':
                    self.send_mail(
                        'game/new_match_alert_email_subject.txt',
                        'game/new_match_alert_email_text.html',
                        context,
                        'game/new_match_alert_email_html.html',
                    )
                case 'match_cancel':
                    self.send_mail(
                        'game/cancel_match_alert_email_subject.txt',
                        'game/cancel_match_alert_email_text.html',
                        context,
                        'game/cancel_match_alert_email_html.html',
                    )
                case _:
                    logger.warn(f'unknown alert: {type}')
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

    def block_rival(self, rival):
        self.block.add(rival)

    def blocked(self):
        blocker = self.block.all()
        blockee = Person.objects.filter(block__in=[self]).all()
        return list(blocker | blockee)

    def facet(self):
        return Person.hash(self.user.email)[:3].upper()

    def send_mail(
        self,
        subject_template_name,
        email_template_name,
        context,
        html_email_template_name=None,
    ):
        logger.info('Person.send_mail()')
        if self.notify_email:
            try:
                subject = loader.render_to_string(subject_template_name, context)
                # Email subject *must not* contain newlines
                subject = "".join(subject.splitlines())
                email_message = EmailMultiAlternatives(
                    subject,
                    loader.render_to_string(email_template_name, context),
                    'accounts@qwikgame.org',
                    [self.user.email]
                )
                # if html_email_template_name is not None:
                #     logger.info(html_email_template_name)
                #     html_email = loader.render_to_string(html_email_template_name, context)
                #     email_message.attach_alternative(html_email, "text/html")
                return email_message.send()
            except Exception:
                logger.exception( "Failed to send email to %s", self )
        return None

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