from django.urls import include, path

from player.views import AvailableView, GameView, InviteView, RivalView

urlpatterns = [
    path("", InviteView.as_view(), name='player'),
    path("game/", AvailableView.as_view(), name='available'),
    path("game/add/", GameView.as_view(), name='game'),
    path("invite/", InviteView.as_view(), name='invite'),
    path("rival/", RivalView.as_view(), name='rival'),
]