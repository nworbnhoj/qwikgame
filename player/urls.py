from django.urls import include, path

from player.views import AvailableView, InviteView, PrivateView, PublicView, UpgradeView, RivalView

urlpatterns = [
    path("", InviteView.as_view(), name='player'),
    path("account/", PublicView.as_view(), name='account'),
    path("account/private/", PrivateView.as_view(), name='private'),
    path("account/public/", PublicView.as_view(), name='public'),
    path("account/upgrade/", UpgradeView.as_view(), name='upgrade'),
    path("game/", AvailableView.as_view(), name='available'),
    path("invite/", InviteView.as_view(), name='invite'),
    path("rival/", RivalView.as_view(), name='rival'),
]