import json, logging, requests, time, urllib.parse
from service.models import Service
from qwikgame.constants import COUNTRIES
from qwikgame.hourbits import Hours24x7, WEEK_ALL

logger = logging.getLogger(__file__)

GEOPLACE = 'geoplace'
GEODETAILS = 'geodetails'
GEOTIMEZONE = 'geotimezone'
GEOCODE = 'geocode'
GEOPLUGIN_CONTEXT = {"http": {"timeout": 1}}

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


    @staticmethod
    def geoplace(text, country=None, region=None):
        geoplace = Service.objects.get(pk=GEOPLACE)
        param = {'input': text}
        if country:
            param['components'] = f'country:{country}'
        if region:
            param['components'] = f'country:{region.country}'
            param['locationbias'] = f'rectangle:{region.south},{region.west}|{region.north},{region.east}'
        return Locate.geo(
            param,
            geoplace.key,
            geoplace.url
        )


    @staticmethod
    def geodetails(placeid):
        geodetails = Service.objects.get(pk=GEODETAILS)
        geo = Locate.geo(
            {'place_id': placeid},
            geodetails.key,
            geodetails.url
        )
        if 'result' in geo:
            return geo['result']
        logger.warn('no result from geodetails: {geo}')
        return None


    @staticmethod
    def geotime(lat, lng):
        geotimezone = Service.objects.get(pk=GEOTIMEZONE)
        location = f"{lat},{lng}"
        return Locate.geo(
            {'location': location, 'timestamp': time.time()},
            geotimezone.key,
            geotimezone.url
        )


    @staticmethod
    def geocode(address, country):
        geocode = Service.objects.get(pk=GEOCODE)
        geo = Locate.geo(
            {'address': address, 'components': f"country:{country}"},
            geocode.key,
            geocode.url
        )
        if 'result' in geo:
            return geo['result']
        logger.warn('no result from geocode: {geo}')
        return None


    @staticmethod
    def revgeocode(lat, lng):
        geocode = Service.objects.get(pk=GEOCODE)
        type_ = "country|administrative_area_level_1|locality"
        geo = Locate.geo(
            {'pos': f"{lat},{lng}", 'result_type': type_},
            geocode.key,
            geocode.url
        )
        if 'result' in geo:
            return geo['result']
        logger.warn('no result from revgeocode: {geo}')
        return None


    @staticmethod
    def get_place(description, country):
        placeid = None
        geoplace = Locate.geoplace(description, country)
        if 'predictions' in geoplace and len(geoplace['predictions']) > 0:
            place = geoplace['predictions'][0]
            placeid = str(place['place_id'])
        return placeid


    @staticmethod
    def get_places(description, region=None):
        places = {}
        geoplace = Locate.geoplace(description, region=region)
        predictions = geoplace.get('predictions', None)
        logger.info(predictions)
        return { str(place['place_id']): str(place['description']) for place in predictions}


    @staticmethod
    def get_details(placeid):
        details = None
        result = Locate.geodetails(placeid)
        if result is not None:
            details = {}
            details['placeid'] = placeid
            details['name'] = result.get('name', '')
            details['address'] = result.get('formatted_address', '')
            details['url'] = result.get('website', '')
            details['phone'] = result.get('international_phone_number', '').replace(' ','')
            editorial_summary = result.get('editorial_summary', None)
            if editorial_summary:
                details['note'] = editorial_summary.get('overview' '')
            geometry = result.get('geometry', None)
            if geometry:
                location = geometry.get('location', None)
                if location:
                    lat = location.get('lat', 0)
                    lng = location.get('lng', 0)
                    details['lat'] = lat
                    details['lng'] = lng
                    details['tz'] = Locate.get_timezone(lat, lng)  # Replace with the actual class name
            for comp in result.get('address_components', []):
                types = comp.get('types', None)
                if types:
                    if 'country' in types:
                        details['country'] = comp.get('short_name', '')
                        # details['country_long'] = comp.get('long_name', '')
                    elif 'administrative_area_level_1' in types:
                        details['admin1'] = comp.get('short_name', '')
                        # details['admin1_long'] = comp.get('long_name', '')
                    elif 'administrative_area_level_2' in types:
                        details['suburb'] = comp.get('short_name', '')
                    elif 'locality' in types: 
                        details['locality'] = comp.get('short_name', '')
                        # details['locality_long'] = comp.get('short_name', '')
                    elif 'route' in types:
                        details['route'] = comp.get('short_name', '')
                    elif 'street_number' in types:
                        details['str_num'] = comp.get('short_name', '')

            opening_hours = result.get('opening_hours', None)
            if opening_hours:
                hours = Hours24x7()
                for period in opening_hours.get('periods', []):
                    open = period.get('open')
                    close = period.get('close')
                    if open and close:
                        try:
                            open_day = int(open.get('day'))
                            open_hour = int(open.get('time'))
                            close_day = int(close.get('day'))
                            close_hour = int(close.get('time'))
                            first_hour = (open_hour // 100) if ((open_hour % 100) == 0) else ((open_hour // 100) + 1)
                            last_hour = (close_hour // 100)
                            hours.set_period(open_day, first_hour, close_day, last_hour)
                        except:
                            logger.exception('Invalid opening hours period {period}')
                details['hours'] = hours.as_bytes()
            else:
                details['hours'] = WEEK_ALL
                logger.warn(f'opening_hours unavailable ({placeid}) - default to 24x7')
        return details


    @staticmethod
    def get_timezone(lat, lng):
        geotime = Locate.geotime(lat, lng)
        return str(geotime['timeZoneId']) if 'timeZoneId' in geotime else ''


    @staticmethod
    def parse_address(address, country=None):
        parsed = False
        placeid = Locate.get_place(address, country)
        if placeid is not None:
            parsed = Locate.get_details(placeid)
        return parsed


    @staticmethod
    def guess_timezone(location, admin1, country):
        tz = None
        placeid = Locate.get_place(f"{location}, {admin1}", country)
        if placeid is not None:
            details = Locate.geodetails(placeid)
            if 'geometry' in details and 'location' in details['geometry']:
                loc = details['geometry']['location']
                tz = Locate.get_timezone(loc['lat'], loc['lng'])
        return tz


    @staticmethod
    def geolocate(key):
        global geo
        if 'geo' not in globals():
            if 'REMOTE_ADDR' in os.environ:
                remote_add = os.environ['REMOTE_ADDR']
                url = f"http://www.geoplugin.net/php.gp?ip={remote_add}"
                response = requests.get(url)
                geo = json.loads(response.text)
                if geo is None:
                    return None  # geoplugin.net is offline
        if isinstance(key, list):
            result = {}
            for k in key:
                geo_key = f"geoplugin_{k}"
                result[k] = geo.get(geo_key, None)
            return result
        else:
            geo_key = f"geoplugin_{key}"
            return geo.get(geo_key, None)

    def __init__(self):
        super().__init__()


    @staticmethod
    def geo_guess(input):
        result = "{}"
        param = {
            'input': input,
            'key': Locate.geoplace.key('private')
        }
        url = Locate.geoplace.url("json")
        try:
            query = urllib.parse.urlencode(param)
            response = requests.get(f"{url}?{query}")
            json_data = response.text
            decoded = json.loads(json_data)
            status = str(decoded["status"])
            if status in ['OK', 'ZERO_RESULTS']:
                result = json_data
            else:
                raise RuntimeError(status)
        except RuntimeError as e:
            msg = str(e)
            logger.warn(f"Google geocoding: {msg}\n{url}?{query}\n{msg}")
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


    @staticmethod
    def get_geometry(country, admin1, locality):
        result = None
        if admin1 and locality:
            input_str = f"{locality}, {admin1}"
        elif admin1:
            input_str = admin1
        elif locality:
            input_str = locality
        elif country in COUNTRIES:
            input_str = COUNTRIES[country]
        else:
            logger.warn(f"Locate::getGeometry({country}, {admin1}, {locality}) insufficient parameters")
            return
        placeid = Locate.get_place(input_str, country)
        if placeid:
            details = Locate.geodetails(placeid)
            if details:
                result = details.get('geometry', {})
                result['placeid'] = details.get('place_id', placeid)
                result['names'] = {}
                tipes = ['country', 'admin1', 'locality']
                for comp in details.get('address_components', []):
                    tipes = comp.get('types', [])
                    if 'country' in tipes:
                        result['names']['country'] = comp['long_name'] or comp['short_name'] or country
                    if 'administrative_area_level_1' in tipes:
                        result['names']['admin1'] = comp['long_name'] or comp['short_name'] or admin1
                    if 'locality' in tipes:
                        result['names']['locality'] = comp['long_name'] or comp['short_name'] or locality
        return result
