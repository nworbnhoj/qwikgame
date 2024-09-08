from django.urls import include, path
from game.views import ChatView, MatchView

urlpatterns = [
    path("match/", MatchView.as_view(), name='match'),
    path("match/<int:match>/", ChatView.as_view(), name='chat'),
]