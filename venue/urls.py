from django.urls import include, path

from venue.views import PlacesBulkView

urlpatterns = [
    # ex: /
    path("bulk", PlacesBulkView.as_view(), name='places_bulk'),
]