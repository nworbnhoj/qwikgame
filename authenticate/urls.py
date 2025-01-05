from django.urls import path

from .views import SignUpView

from . import views

urlpatterns = [
    # ex: /authenticate/
    path("", views.index, name="index"),
    path("signup/", SignUpView.as_view(), name="signup"),
]