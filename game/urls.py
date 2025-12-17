from django.urls import include, path
from game.views import MatchView, MatchesView, ReviewView, ReviewsView, RivalView
from qwikgame.views import ServiceWorkerView

urlpatterns = [
    path("match/", MatchesView.as_view(), name='matches'),
    path("match/<int:match>/", MatchView.as_view(), name='match'),
    path("match/<int:match>/<rival>/", RivalView.as_view()),
    path("match/<int:match>/<rival>/<path:ignore>", RivalView.as_view()),
    path("match/review/", ReviewsView.as_view(), name='review'),
    path("match/review/<int:review>/", ReviewView.as_view(), name='review'),
    path('sw.js', ServiceWorkerView.as_view(), name='sw'),
]