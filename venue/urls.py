from django.urls import include, path

from venue.views import PlacesBulkView, VenueView
from qwikgame.views import ServiceWorkerView

urlpatterns = [
    # ex: /
    path("bulk", PlacesBulkView.as_view(), name='places_bulk'),
    path("<int:venue>/", VenueView.as_view(), name='venue'),
    path('sw.js', ServiceWorkerView.as_view(), name='sw'),
]