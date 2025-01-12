from django.urls import include, path

from player.views import AppealsView, FilterView, FriendView, FriendAddView, FriendsView, InvitationView, RivalView, FiltersView

urlpatterns = [
    path("", AppealsView.as_view()),
    path("filter/", FilterView.as_view(), name='filter'),
    path("filters/", FiltersView.as_view(), name='filters'),
    path("friend/", FriendsView.as_view(), name='friend'),
    path("friend/add/", FriendView.as_view(), name='friend_add'),
    path("friend/<int:friend>/", FriendView.as_view(), name='friend'),
    path("rival/", RivalView.as_view(), name='rival'),
]