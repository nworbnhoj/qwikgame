import json, logging, requests, time, urllib.parse
from api.models import Service

logger = logging.getLogger(__file__)

GEOCODE = 'geocode'

class Locate:

    @staticmethod
    def geo(param, key, url):
        result = None
        param['key'] = key
        try:
            response = requests.post(url, params=param)
            response.raise_for_status()
            result = response.json()
        except:
            logger.exception("Google geocoding: {}?{}\n{}".format(url, param, response))
        return result

        return geo['result'] if 'result' in geo else None


    @staticmethod
    def revgeocode(lat, lng):
        geocode = Service.objects.get(pk=GEOCODE)
        type_ = "country|administrative_area_level_1|locality"
        geo = Locate.geo(
            {'latlng': f"{lat},{lng}", 'result_type': type_},
            geocode.key,
            geocode.url
        )
        return geo['results'] if 'results' in geo else None
        return result

    @staticmethod
    def get_address(lat, lng):
        address = {}
        revgeocode = Locate.revgeocode(lat, lng)
        if revgeocode and 'address_components' in revgeocode[0]:
            components = revgeocode[0]['address_components']
            for component in components:
                types = component.get('types', [])
                for type in types:
                    if type == 'country':
                        address['country'] = str(component['short_name'])
                    elif type == 'administrative_area_level_1':
                        name = str(component['short_name']) or str(component['long_name']) or ' '
                        address['admin1'] = name
                    elif type == 'locality':
                        address['locality'] = str(component['short_name'])
        return address
