from django.urls import include, path

from player.views import AccountView

urlpatterns = [
    # ex: /
    path("", AccountView.as_view()),
]