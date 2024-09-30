from django.urls import include, path

from player.views import BidView, FilterView, InvitationView, KeenView, ReplyView, RivalView, BidView, ScreenView

urlpatterns = [
    path("", InviteView.as_view(), name='player'),
    path("filter/", FilterView.as_view(), name='filter'),
    path("invite/<int:invite>/", RsvpView.as_view(), name='rsvp'),
    path("invite/", InviteView.as_view(), name='invite'),
    path("keen/<int:appeal>/", BidView.as_view(), name='bid'),
    path("keen/", KeenView.as_view(), name='keen'),
    path("rival/", RivalView.as_view(), name='rival'),
    path("screen/", ScreenView.as_view(), name='screen'),
]