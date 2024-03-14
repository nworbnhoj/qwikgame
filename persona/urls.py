from django.urls import include, path
from persona.views import AccountView

urlpatterns = [
    # ex: /
    path("", AccountView.as_view()),
]