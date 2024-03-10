from django.urls import path

from . import views

urlpatterns = [
    # ex: /authenticate/
    path("", views.index, name="index"),
]