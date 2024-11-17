from django.db import models


LANGUAGE = [
    ('bg', 'български'),
    ('en', 'English'),
    ('es', 'Español'),
    ('zh', '中文'),
    ('ru', 'русский'),
    ('fr', 'Français'),
    ('hi', 'हिंदी'),
    ('ar', 'اللغة العربية'),
    ('jp', '日本語'),
]


class Person(models.Model):
    icon = models.CharField(max_length=16)
    language = models.CharField(max_length=2, choices=LANGUAGE, default='en',)
    location_auto = models.BooleanField(default=False)
    name = models.CharField(max_length=32)
    notify_email = models.BooleanField(default=True)
    notify_web = models.BooleanField(default=False)
    user = models.OneToOneField('authenticate.User', on_delete=models.CASCADE)

    def __str__(self):
    	return self.name


class Social(models.Model):
    person = models.ForeignKey(Person, on_delete=models.CASCADE)
    url = models.URLField(max_length=255)

    def __str__(self):
        return self.url