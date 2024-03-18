from django.urls import include, path
from person.views import AccountView

urlpatterns = [
    path("", AccountView.as_view()),
    path("manager/", include("venue.urls")),
    path("player/", include("player.urls")),
]