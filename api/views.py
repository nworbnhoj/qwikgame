import json, logging, math
from django.http import JsonResponse
from api.forms import REGION_KEYS, VenueMarksJson
from api.models import Mark
from game.models import Game
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
            country = region.get('country')
            if country:
                region_objects = Region.objects.filter(country=country)
                admin1 = region.get('admin1')
                if admin1:
                    region_objects = region_objects.filter(admin1=admin1)
                    locality = region.get('locality')
                    if locality:
                        region_objects = region_objects.filter(locality=locality)
                    else:
                        region_objects = region_objects.filter(locality__isnull=True)
                else:
                    region_objects = region_objects.filter(admin1__isnull=True)
                regions = region_objects.all()
            else:
                logger.warn('region missing country')
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
        mark_objects = Mark.objects
        game = Game.objects.filter(code=context.get(GAME)).first()
        if game:
            mark_objects = mark_objects.filter(game=game)
        # The client can supply a list of "|country|admin1|locality" keys 
        # which are already in-hand, and not required in the JSON response.    
        avoidable = context.get(AVOIDABLE)
        if avoidable:
            for avoid in avoidable.rsplit(':'):
                place = avoid.rsplit('|')
                place.reverse()
                place = dict(zip(REGION_KEYS, place))
                country = place.get('country')
                admin1 = place.get('admin1')
                locality = place.get('locality')
                if locality:
                    mark_objects = mark_objects.exclude(place__country=country, place__admin1=admin1, place__locality=locality)
                elif admin1:
                    mark_objects = mark_objects.exclude(place__country=country, place__admin1=admin1)
                elif country:
                    mark_objects = mark_objects.exclude(place__country=country)

        marks = {}
        for region in regions:
            country = region.country
            admin1 = region.admin1
            locality = region.locality
            if locality:
                # get Venue Marks
                venue_marks = mark_objects.filter(place__country=country, place__admin1=admin1, place__locality=locality)
                marks = marks | {vm.key(): vm.mark() for vm in venue_marks.all()}
            if not game:
                mark_objects = mark_objects.filter(game__isnull=True)
            if admin1:
                # get locality marks
                locality_marks = mark_objects.filter(place__country=country, place__admin1=admin1, place__locality__isnull=False)
                marks = marks | {lm.key(): lm.mark() for lm in locality_marks.all()}
            if country:
                # get admin1 marks
                admin1_marks = mark_objects.filter(place__country=country, place__admin1__isnull=False, place__locality__isnull=True)
                marks = marks | {am.key(): am.mark() for am in admin1_marks.all()}
            # get country marks
            country_marks = mark_objects.filter(place__country__isnull=False, place__admin1__isnull=True, place__locality__isnull=True)
            marks = marks | {cm.key(): cm.mark() for cm in country_marks.all()}

        response = {
            STATUS: 'OK' if marks else 'NO_RESULTS',
            GAME: game.code if game else 'ANY',
            MARKS: marks,
        }

        # find the closest region and include in the response
        if len(regions) == 0:
            closest = {}
        elif len(regions) == 1:
            for k,v in regions[0].place().items():
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
            for k,v in closest.place().items():
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