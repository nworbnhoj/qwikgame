from django.urls import include, path
from game.views import ActiveView, AvailableView, GameView

urlpatterns = [
    path("", GameView.as_view(), name='game'),
    path("active/", ActiveView.as_view(), name='active'),
    path("<str:game>/", AvailableView.as_view(hide=['game']), name='available_add'),
    path("<str:game>/<str:venue>/", AvailableView.as_view(), name='available'),
]