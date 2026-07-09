from __future__ import absolute_import, unicode_literals
import os
from celery import Celery

os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'redismail.settings')

app = Celery('redismail')
app.config_from_object('django.conf:settings', namespace='CELERY')

app.conf.enable_utc = True

app.conf.update(timezone = 'UTC')

app.autodiscover_tasks()
