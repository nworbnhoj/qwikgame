from django.urls import include, path
from game.views import MatchView, MatchesView, ReviewView

urlpatterns = [
    path("match/", MatchesView.as_view(), name='matches'),
    path("match/<int:match>/", MatchView.as_view(), name='match'),
    path("match/review/", ReviewView.as_view(), name='review'),
    path("match/<int:match>/review/", ReviewView.as_view(), name='review'),
]