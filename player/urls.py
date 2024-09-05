from django.urls import include, path

from player.views import InviteView, InvitationView, KeenView, ReplyView, RivalView, RsvpView, ScreenView

urlpatterns = [
    path("", InviteView.as_view(), name='player'),
    path("invite/<int:invite>/", RsvpView.as_view(), name='rsvp'),
    path("invite/", InviteView.as_view(), name='invite'),
    path("keen/<int:appeal>/", ReplyView.as_view(), name='reply'),
    path("keen/", KeenView.as_view(), name='keen'),
    path("rival/", RivalView.as_view(), name='rival'),
    path("screen/", ScreenView.as_view(), name='screen'),
]