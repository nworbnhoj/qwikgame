from django.urls import include, path
from persona.views import AccountView

urlpatterns = [
    # ex: /
    path("", AccountView.as_view()),
    # ex: /manager/
    path("manager/", include("venue.urls")),
    # ex: /player/
    path("player/", include("player.urls")),
]