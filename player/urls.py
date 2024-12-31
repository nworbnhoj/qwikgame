from django.urls import include, path

from player.views import AcceptView, BidView, AppealsView, FilterView, FriendView, FriendAddView, FriendsView, InvitationView, KeenView, RivalView, BidView, FiltersView

urlpatterns = [
    path("", AppealsView.as_view()),
    path("appeal/", AppealsView.as_view(), name='appeal'),
    path("appeal/filter/", FilterView.as_view(), name='filter'),
    path("appeal/filters/", FiltersView.as_view(), name='filters'),
    path("appeal/<int:appeal>/", BidView.as_view(), name='bid'),
    path("appeal/keen/", KeenView.as_view(), name='keen'),
    path("appeal/keen/<str:game>/", KeenView.as_view(), name='keen'),
    path("appeal/accept/<int:appeal>/", AcceptView.as_view(), name='accept'),
    path("friend/", FriendsView.as_view(), name='friend'),
    path("friend/add/", FriendView.as_view(), name='friend_add'),
    path("friend/<int:friend>/", FriendView.as_view(), name='friend'),
    path("rival/", RivalView.as_view(), name='rival'),
]