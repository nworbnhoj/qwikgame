from django.urls import include, path

from player.views import AppealsView, FilterView, FriendView, FriendAddView, FriendStrengthView, FriendsView, InvitationView, RivalView, FiltersView
from qwikgame.views import ServiceWorkerView

urlpatterns = [
    path("", AppealsView.as_view()),
    path("filter/", FilterView.as_view(), name='filter'),
    path("filters/", FiltersView.as_view(), name='filters'),
    path("friend/", FriendsView.as_view(), name='friend'),
    path("friend/add/", FriendAddView.as_view(), name='friend_add'),
    path("friend/<int:friend>/", FriendView.as_view(), name='friend'),
    path("friend/<int:friend>/strength/", FriendStrengthView.as_view(), name='friend_strength'),
    path("rival/", RivalView.as_view(), name='rival'),
    path('sw.js', ServiceWorkerView.as_view(), name='sw'),
]