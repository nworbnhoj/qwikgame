from django.urls import path

from .views import EmailValidateDoneView, EmailValidationHandleView, EmailValidateView, SignUpView

from . import views

urlpatterns = [
    # ex: /authenticate/
    path("", views.index, name="index"),
    path("signup/", SignUpView.as_view(), name="signup"),
    path("validate/", EmailValidateView.as_view(), name="validate"),
    path("validate/done/", views.EmailValidateDoneView.as_view(), name="email_validate_done"),
    path("validate/<uidb64>/<token>/", views.EmailValidationHandleView.as_view(), name="validate_email_handle"),
]