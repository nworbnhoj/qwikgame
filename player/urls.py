from django.urls import include, path

from player.views import ActiveView, AvailableView, GameView, InviteView, RivalView

urlpatterns = [
    path("", InviteView.as_view(), name='player'),
    path("game/", GameView.as_view(), name='game'),
    path("game/active/", ActiveView.as_view(), name='active'),
    path("game/<game>/<venue>/", AvailableView.as_view(), name='available'),
    path("invite/", InviteView.as_view(), name='invite'),
    path("rival/", RivalView.as_view(), name='rival'),
]