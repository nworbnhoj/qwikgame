from django.urls import include, path

from player.views import AcceptView, BidView, FeedView, FilterView, FriendView, FriendsView, InvitationView, KeenView, RivalView, BidView, FiltersView

urlpatterns = [
    path("", FeedView.as_view()),
    path("feed/", FeedView.as_view(), name='feed'),
    path("feed/filter/", FilterView.as_view(), name='filter'),
    path("feed/filters/", FiltersView.as_view(), name='filters'),
    path("feed/<int:appeal>/", BidView.as_view(), name='bid'),
    path("feed/keen/", KeenView.as_view(), name='keen'),
    path("feed/accept/<int:appeal>/", AcceptView.as_view(), name='accept'),
    path("friend/", FriendsView.as_view(), name='friend'),
    path("friend/<int:friend>/", FriendView.as_view(), name='friend'),
    path("rival/", RivalView.as_view(), name='rival'),
]