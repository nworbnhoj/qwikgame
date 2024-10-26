import logging
from api.models import Mark
from django.shortcuts import redirect, render
from game.models import Game
from service.locate import Locate
from venue.forms import GoogleSearchForm, GooglePlacesForm
from venue.models import Place, Region, Venue
from qwikgame.views import QwikView


logger = logging.getLogger(__file__)


class PlacesBulkView(QwikView):
    search_form_class = GoogleSearchForm
    places_form_class = GooglePlacesForm
    template_name = 'venue/places_bulk.html'

    # remove Venues that already exist for Game
    def __new_places(self, places, game=None):
        venue_qs = Venue.objects
        if game:
            venue_qs = venue_qs.filter(games__in=[game])
        return { k:v for k,v in places.items() if not venue_qs.filter(placeid=k).exists()}

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        context |= self.search_form_class.get()
        return render(request, self.template_name, context)


    def post(self, request, *args, **kwargs):
        if not request.user.is_admin:
            return redirect('home')
        super().post(request, *args, **kwargs)
        if 'search' in request.POST:
            context = self.search_form_class.post(request.POST)
            search_form = context.get('search_form')
            if search_form:
                game = Game.objects.filter(code=context.get('game')).first()
                query = context.get('query')
                if search_form.is_valid() and game and query:
                    region = Region.objects.filter(pk=context.get('region')).first()
                    if region:
                        places = Locate.get_places(query, region)
                        places = self.__new_places(places, game)
                        request.session['game'] = game.code
                        request.session['place_choices'] = places
                        request.session['query'] = query
                        request.session['region'] = region.pk
                        context |= self.places_form_class.get(game.name, places)
                else:
                    context |= self.places_form_class.get(game.name)
        elif 'add' in request.POST:
            context = self.places_form_class.post(
                request.POST,
                request.session.get('game'),
                request.session.get('place_choices')
            )
            places_form = context.get('places_form')
            if places_form:
                game = Game.objects.filter(code=request.session['game']).first()
                place_ids = context.get('places')
                if places_form.is_valid() and game and place_ids:
                    for placeid in place_ids:
                        venue = Venue.objects.filter(placeid=placeid).first()
                        if not venue:
                            venue = Venue.from_placeid(placeid)
                            if venue:
                                venue.save()
                                logger.info(f'Venue new: {venue}')
                            else:
                                logger.warn(f'Failed to create new Venue: {placeid}')                                
                        if venue and not Venue.objects.filter(games__in=game.pk):
                            venue.games.add(game)
                            logger.info(f'Venue add Game: {game}')
                            venue.save()
                            mark = Mark(game=game, place=venue, size=1)
                            mark.save()
                            logger.info(f'Mark new {mark}')
                    places = self.__new_places(request.session['place_choices'])
                    request.session['place_choices'] = places
                    context |= self.places_form_class.get(game.name, places)
            context |= self.search_form_class.get(
                request.session.get('game'),
                request.session.get('query'),
                request.session.get('region'),
            )
        else:
            return redirect('places_bulk')
        return render(request, self.template_name, context)