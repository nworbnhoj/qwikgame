import json, logging
from django.http import JsonResponse
from api.forms import AVOIDABLE, ERRORS, GAME, LAT, LNG, REGION
from api.forms import VenueMarksJson
from player.models import Filter
from venue.models import Venue
from qwikgame.locate import Locate
from qwikgame.views import QwikView

logger = logging.getLogger(__file__)

# key constants
ADMIN1    = 'admin1';
COUNTRY   = 'country';
LOCALITY  = 'locality';
MARKS     = 'marks';
NAME      = 'name'
MSG       = 'msg';
STATUS    = 'status';
VENUE     = 'venue';

REGION_KEYS = [COUNTRY, ADMIN1, LOCALITY]
SUMMARY_THRESHOLD = 100;

class DefaultJson(QwikView):

    def post(self, request, *args, **kwargs):
        super().get(request)
        return JsonResponse({
            STATUS : 'OK',
            INFO : 'Welcome to the qwikgame API',
        })


class VenueMarksJson(QwikView):
    venue_marks_json_class = VenueMarksJson

    def post(self, request, *args, **kwargs):
        super().post(request)
        context = self.venue_marks_json_class.post(
            request.body,
        )

        if context.get(ERRORS):
            msg = "Invalid api request: {}".format(context.get(ERRORS))
            logger.warn(msg)
            return JsonResponse({
                STATUS: 'error', 
                MSG: msg,
            })
 
        logger.info('API venue_marks request:\n\t{}'.format(context))

        game = context.get(GAME)
        region = context.get(REGION)
        lat = context.get(LAT)
        lng = context.get(LNG)

        if not region:
            # get the region from lat-lng coordinates
            region = Locate.get_address(lat, lng);
            logger.info('Obtained region for ({} {}):\n\t{}'.format(lat, lng, region))
        if not region:
            msg = 'failed to obtain country|admin1|locality for {} {}'.format(context[LAT], context[LNG])
            logger.warn(msg)
            return JsonResponse({
                STATUS: 'error', 
                MSG: msg,
            })

        marks = self._get_venue_marks(region, context.get('AVOIDABLE'))

        json_response = JsonResponse({
            STATUS: 'OK' if marks else 'NO_RESULTS',
            GAME: game.code,
            COUNTRY: region.get(COUNTRY),
            ADMIN1: region.get(ADMIN1),
            LOCALITY: region.get(LOCALITY),
            MARKS: marks,
        })

        logger.info('API response venue_marks response:\n\t{}'.format(json_response))
 
        return json_response

    def _get_venue_marks(self, region, avoidable=None):
        venues = Venue.objects.filter(**region)
        # The client can supply a list of "|country|admin1|locality" keys 
        # which are already in-hand, and not required in the JSON response.    
        if avoidable:
            for avoid in avoidable:
                region = dict(zip(REGION_KEYS, avoid.rsplit('|').reverse()))
                venues = venues.exclude(**region)
        marks = [{LAT:v.lat, LNG:v.lng, NAME:v.name} for v in venues]
        for mark in marks:
            mark['num'] = Filter.objects.filter(venue=mark.get(VENUE)).count()
        return marks