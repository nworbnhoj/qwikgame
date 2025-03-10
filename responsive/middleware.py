from __future__ import unicode_literals

import datetime
import re

from django.template.loader import render_to_string
from django.utils.cache import patch_vary_headers
from django.utils.encoding import smart_bytes
from django.utils.deprecation import MiddlewareMixin


from .conf import settings
from .utils import Device


class ResponsiveMiddleware(MiddlewareMixin):
    pattern = re.compile(b'<head()>|<head (.*?)>', re.IGNORECASE)
    snippet = smart_bytes(render_to_string('responsive/snippet.html', {
        'cookie_name': settings.RESPONSIVE_COOKIE_NAME,
        'cookie_age': 60 * 60 * 24 * settings.RESPONSIVE_COOKIE_AGE,  # convert to secs
    }))

    def __init__(self, get_response):
        super().__init__(get_response)

    def process_request(self, request):
        responsive_cookie = request.COOKIES.get(settings.RESPONSIVE_COOKIE_NAME, None)
        if responsive_cookie:
            parts = responsive_cookie.split(':')
            if len(parts) != 3:
                request.INVALID_RESPONSIVE_COOKIE = True
                return

            try:
                width, height, pixel_ratio = parts
                width, height, pixel_ratio = int(width), int(height), float(pixel_ratio)
            except ValueError:
                request.INVALID_RESPONSIVE_COOKIE = True
                return

            device_info = {
                'width': width,
                'height': height,
                'pixel_ratio': pixel_ratio
            }

            device = Device(**device_info)
        else:
            device = Device()

        setattr(request, settings.RESPONSIVE_VARIABLE_NAME, device)

    def process_response(self, request, response):
        html_types = ('text/html', 'application/xhtml+xml')
        content_type = response.get('Content-Type', '').split(';')[0]
        content_encoding = response.get('Content-Encoding', '')
        if any((getattr(response, 'streaming', False),
                'gzip' in content_encoding,
                content_type not in html_types)):
            return response

        response.content = self.pattern.sub(b'<head\g<1>>' + self.snippet, response.content)

        if response.get('Content-Length', None):
            response['Content-Length'] = len(response.content)

        patch_vary_headers(response, ('Cookie', ))
        return response
