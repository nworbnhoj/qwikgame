import json, logging, math
from django.http import JsonResponse
from api.forms import REGION_KEYS, VenueMarksJson
from api.models import Mark
from player.models import Filter
from venue.models import Region, Venue
from qwikgame.constants import ADMIN1, AVOIDABLE, COUNTRY, ERRORS, GAME, LAT, LNG, LOCALITY, MARKS, POS, REGION, STATUS
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

        regions = None
        region = context.get(REGION)
        pos = context.get(POS)
        if region:
            region_objects = Region.objects.filter(**region)
            if not region.get(LOCALITY, None):
                region_objects = region_objects.exclude(region__locality__isnull=False)
            if not region.get(ADMIN1, None):
                region_objects = region_objects.exclude(admin1__locality__isnull=False)
            regions = region_objects.all()
            logger.info(f'API venue_marks request: {region}')
        elif pos:
            lat, lng = pos[LAT], pos[LNG]
            logger.info(f'API venue_marks request: ({lat} {lng})')
            regions = Region.objects.filter(
                east__gte=lng,
                north__gte=lat,
                south__lte=lat,
                west__lte=lng,
                )
            logger.info(f'Regions for ({lat} {lng}): {[ r.name for r in regions]}')
        else:
            msg = 'failed to obtain marks (missing both region and lat-lng)'
            logger.warn(msg)
            return JsonResponse({
                STATUS: 'error', 
                MSG: msg,
            })

        game = context.get(GAME, None)
        kwargs = {GAME: game} if game else {}
        mark_objects =  Mark.objects.filter(**kwargs)

        # The client can supply a list of "|country|admin1|locality" keys 
        # which are already in-hand, and not required in the JSON response.    
        avoidable = context.get(AVOIDABLE)
        if avoidable:
            for avoid in avoidable.rsplit(':'):
                place = avoid.rsplit('|')
                place.reverse()
                place = dict(zip(REGION_KEYS, place))
                kwargs = Mark.place_filter(place)
                mark_objects = mark_objects.exclude(**kwargs)

        marks = {}
        for region in regions:
            place = region.place_dict()
            if place.get(LOCALITY, None):
                # get Venue Marks
                kwargs = Mark.place_filter(place)
                venue_marks = mark_objects.filter(**kwargs).all()
                marks = marks | {vm.key(): vm.mark() for vm in venue_marks}
                place.pop(LOCALITY, None)
            if place.get(ADMIN1, None):
                # get locality marks
                kwargs = Mark.place_filter(place)
                locality_marks = mark_objects.filter(**kwargs)
                marks = marks | {lm.key(): lm.mark() for lm in locality_marks}
                place.pop(ADMIN1, None)
            if place.get(COUNTRY, None):
                # get admin1 marks
                kwargs = Mark.place_filter(place)
                admin1_marks = mark_objects.filter(**kwargs)
                admin1_marks = admin1_marks.exclude(region__locality__isnull=True)
                marks = marks | {am.key(): am.mark() for am in admin1_marks}
            # get country marks
            country_marks = mark_objects.exclude(region__locality__isnull=True)
            country_marks = country_marks.exclude(region__admin1__isnull=True)
            marks = marks | {cm.key(): cm.mark() for cm in country_marks}

        response = {
            STATUS: 'OK' if marks else 'NO_RESULTS',
            GAME: game,
            MARKS: marks,
        }

        # find the closest region and include in the response
        if len(regions) == 0:
            closest = {}
        elif len(regions) == 1:
            for k,v in regions[0].place_dict().items():
                response[k] = v
        else:
            closest = None
            min_distance = 1000 # arbitrary big number > 360
            for region in regions:
                lat = region.lat - pos[LAT]
                lng = region.lng - pos[LNG]
                distance = math.sqrt(lat**2 + lng**2)
                if distance < min_distance:
                    min_distance = distance
                    closest = region
            for k,v in closest.place_dict().items():
                response[k] = v

        json_response = JsonResponse(response)
        logger.info(f'API venue_marks response: status={json_response.status_code} marks={len(marks)}')
        return json_response


    def locate(lat, lng):
        regions = Region.objects.filter(
            east__lte=lng,
            north__gte=lat,
            south__lte=lat,
            west__gte=lng
            )