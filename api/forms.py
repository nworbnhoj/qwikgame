import json, logging
from qwikgame.forms import QwikForm
from django.forms import CharField, ChoiceField, DecimalField
from game.models import Game
from venue.models import Venue
from qwikgame.constants import ADMIN1, AVOIDABLE, COUNTRY, ERRORS, GAME, LAT, LNG, LOCALITY, POS, REGION


logger = logging.getLogger(__file__)

REGION_KEYS = [COUNTRY, ADMIN1, LOCALITY]

class VenueMarksJson(QwikForm):
    game = ChoiceField( 
        choices = {'ANY':'Any Game'} | Game.choices(),
        required=True,
    )
    lat = DecimalField(
        decimal_places=6,
        max_value=180.0,
        min_value=-180.0,
        required=False,
    )
    lng = DecimalField(
        decimal_places=6,
        max_value=180.0,
        min_value=-180.0,
        required=False,
    )
    region = CharField(
        required=False,
    )


    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)


    def clean(self):
        cleaned_data = super().clean()
        region = cleaned_data.get(REGION)
        lat = cleaned_data.get(LAT)
        lng = cleaned_data.get(LNG)

        if not region:
            if not lat and not lng:
                raise ValidationError(
                    "Either region or lat-lng are required."
                )
            if not lat or not lng:
                raise ValidationError(
                    "Both lat and lng are required."
                )


    @classmethod
    def post(klass, request_body):
        context={
            AVOIDABLE: None,
            ERRORS: None,
            GAME: None,
            LAT: None,
            LNG: None,
            REGION: None,
        }
        values = json.loads(request_body.decode('utf8'))
        form = klass(data=values)
        if form.is_valid():
            context[GAME]=form.cleaned_data[GAME]
            context[AVOIDABLE] = form.cleaned_data.get(AVOIDABLE)
            lat = form.cleaned_data.get(LAT)
            lng = form.cleaned_data.get(LNG)
            if lat and lng:
                context[POS] = {LAT:lat, LNG:lng}
            region = form.cleaned_data.get(REGION)
            if region:
                context[REGION] = dict(zip(REGION_KEYS, region.rsplit('|').reverse()))
        else:
            logger.warn("invalid form: {}".format(form.errors))
            context[ERRORS] = form.errors
        return context