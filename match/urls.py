from django.urls import include, path

from match.views import MatchView

urlpatterns = [
    # ex: /match/
    path("", MatchView.as_view(), name='match'),
]