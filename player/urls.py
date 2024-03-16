from django.urls import include, path

from player.views import AccountView, AvailableView, InviteView, RivalView

urlpatterns = [
    path("", AccountView.as_view(), name='account'),
    path("game/", AvailableView.as_view(), name='available'),
    path("invite/", InviteView.as_view(), name='invite'),
    path("rival/", RivalView.as_view(), name='rival'),
]