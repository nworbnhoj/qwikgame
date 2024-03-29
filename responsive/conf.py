from django.conf import settings  # noqa
from django.utils.translation import gettext_lazy as _

from django.apps import AppConfig


class ResponsiveAppConf(AppConfig):
    name = 'responsive'

    class Meta:
        prefix = 'responsive'
