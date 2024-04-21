from django.urls import include, path

from player.views import InviteView, InvitationView, KeenView, ReplyView, RivalView, RsvpView

urlpatterns = [
    path("", InviteView.as_view(), name='player'),
    path("invite/", InviteView.as_view(), name='invite'),
    path("invite/keen/<int:appeal>/", ReplyView.as_view(), name='reply'),
    path("invite/keen/", KeenView.as_view(), name='keen'),
    path("rival/", RivalView.as_view(), name='rival'),
]