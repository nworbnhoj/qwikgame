from django.urls import include, path

from venue.views import PlacesBulkView, VenueAddView, VenueView, VenuesView
from qwikgame.views import ServiceWorkerView

urlpatterns = [
    path("", VenuesView.as_view()),
    path("bulk", PlacesBulkView.as_view(), name='places_bulk'),
    path("add/", VenueAddView.as_view(), name='venue_add'),
    path("<int:venue>/", VenueView.as_view(), name='venue'),
    path('sw.js', ServiceWorkerView.as_view(), name='sw'),
]