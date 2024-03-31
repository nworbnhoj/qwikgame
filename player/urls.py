from django.urls import include, path

from player.views import AvailableView, InviteView, RivalView

urlpatterns = [
    path("", InviteView.as_view(), name='player'),
    path("game/", AvailableView.as_view(), name='available'),
    path("invite/", InviteView.as_view(), name='invite'),
    path("rival/", RivalView.as_view(), name='rival'),
]