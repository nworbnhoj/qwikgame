from django.urls import include, path

from player.views import BidView, FeedView, FilterView, InvitationView, KeenView, ReplyView, RivalView, BidView, FiltersView

urlpatterns = [
    path("", FeedView.as_view()),
    path("feed/", FeedView.as_view(), name='feed'),
    path("feed/filter/", FilterView.as_view(), name='filter'),
    path("feed/filters/", FiltersView.as_view(), name='filters'),
    path("feed/<int:appeal>/", BidView.as_view(), name='bid'),
    path("feed/keen/", KeenView.as_view(), name='keen'),
    path("feed/reply/<int:appeal>/", ReplyView.as_view(), name='reply'),
    path("rival/", RivalView.as_view(), name='rival'),
]