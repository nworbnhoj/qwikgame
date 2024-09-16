import json, logging
from django.http import JsonResponse
from api.forms import REGION_KEYS, VenueMarksJson
from api.models import Mark, Region
from player.models import Filter
from venue.models import Venue
from qwikgame.constants import ADMIN1, AVOIDABLE, COUNTRY, ERRORS, GAME, LAT, LNG, LOCALITY, MARKS, POS, REGION, STATUS
from qwikgame.locate import Locate
from qwikgame.views import QwikView

logger = logging.getLogger(__file__)

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

        regions = None
        region = context.get(REGION)
        pos = context.get(POS)
        if region:
            regions = [region]
        elif pos:
            lat, lng = pos[LAT], pos[LNG]
            regions = Region.objects.filter(
                east__gte=lng,
                north__gte=lat,
                south__lte=lat,
                west__lte=lng,
                )
            logger.info('Obtained regions for ({} {}):\n\t{}'.format(lat, lng, regions))
        else:
            msg = 'failed to obtain marks (missing both region and lat-lng)'
            logger.warn(msg)
            return JsonResponse({
                STATUS: 'error', 
                MSG: msg,
            })

        game = context.get(GAME, 'ALL')
        marks = []
        for region in regions:
            if region.locality:
                # get Venue Marks
                kwargs = Mark.venue_filter(region.place) | {GAME: game}
                marks = marks + list(Mark.objects.filter(**kwargs).all())
            # get Region Marks
            kwargs = Mark.region_filter(region.place()) | {GAME: game}
            mark = Mark.objects.filter(**kwargs).first()
            if mark:
                marks.append(mark)

        # The client can supply a list of "|country|admin1|locality" keys 
        # which are already in-hand, and not required in the JSON response.    
        avoidable = context.get(AVOIDABLE)
        if avoidable:
            for avoid in avoidable:
                region = dict(zip(REGION_KEYS, avoid.rsplit('|').reverse()))
                marks = marks.exclude(**region)

        # TODO fix this fudge: select the first region, rather than the list, or smallest etc
        region0 = regions[0].place()

        json_response = JsonResponse({
            STATUS: 'OK' if marks else 'NO_RESULTS',
            GAME: game,
            COUNTRY: region0.get(COUNTRY, ''),
            ADMIN1: region0.get(ADMIN1, ''),
            LOCALITY: region0.get(LOCALITY, ''),
            MARKS: [mark.mark() for mark in marks],
        })

        logger.info('API response venue_marks response:\n\t{}'.format(json_response))
 
        return json_response


    def locate(lat, lng):
        regions = Region.objects.filter(
            east__lte=lng,
            north__gte=lat,
            south__lte=lat,
            west__gte=lng
            )