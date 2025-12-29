from django.urls import include, path

from api.views import QwikJson, VenueMarksJson

urlpatterns = [
    path("", QwikJson.as_view(), name='default_json'),
    path("venue_marks/", VenueMarksJson.as_view(), name='venue_marks_json'),
]