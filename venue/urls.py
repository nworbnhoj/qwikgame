from django.urls import include, path

from venue.views import PlacesBulkView, VenueView

urlpatterns = [
    # ex: /
    path("bulk", PlacesBulkView.as_view(), name='places_bulk'),
    path("<int:venue>/", VenueView.as_view(), name='venue'),
]