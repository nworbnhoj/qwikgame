import json, logging
from django.http import JsonResponse
from qwikgame.views import QwikView

logger = logging.getLogger(__file__)

class DefaultJson(QwikView):

    def post(self, request, *args, **kwargs):
        super().get(request)
        return JsonResponse({
            STATUS : 'OK',
            INFO : 'Welcome to the qwikgame API',
        })
