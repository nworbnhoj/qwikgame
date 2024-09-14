from django.urls import include, path

from api.views import DefaultJson, VenueMarksJson

urlpatterns = [
    path("", DefaultJson.as_view(), name='default_json'),
]