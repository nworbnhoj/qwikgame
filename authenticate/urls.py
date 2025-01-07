from django.urls import path

from .views import EmailValidationHandleView, LoginView, LoginSentView, RegisterView, RegisterSentView
from . import views

urlpatterns = [
    # ex: /authenticate/
    path("login/", LoginView.as_view(), name="login"),
    path("login/sent/", views.LoginSentView.as_view(), name="login_sent"),
    path("register/", RegisterView.as_view(), name="register"),
    path("register/sent/", views.RegisterSentView.as_view(), name="register_sent"),
    path("validate/<uidb64>/<token>/", views.EmailValidationHandleView.as_view(), name="validate_email_handle"),
]