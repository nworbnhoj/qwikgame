from django.urls import include, path
from game.views import ActiveView, AvailableView, GameView, MatchView

urlpatterns = [
    path("", GameView.as_view(), name='game'),
    path("active/", ActiveView.as_view(), name='active'),
    path("match/", MatchView.as_view(), name='match'),
    path("<str:game>/", AvailableView.as_view(hide=['game']), name='available_add'),
    path("<str:game>/<str:venue>/", AvailableView.as_view(), name='available'),
]