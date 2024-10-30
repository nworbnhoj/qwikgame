from django.urls import include, path
from game.views import ChatView, MatchView, ReviewView

urlpatterns = [
    path("match/", MatchView.as_view(), name='match'),
    path("match/<int:match>/", ChatView.as_view(), name='chat'),
    path("match/review/", ReviewView.as_view(), name='review'),
    path("match/<int:match>/review/", ReviewView.as_view(), name='review'),
]