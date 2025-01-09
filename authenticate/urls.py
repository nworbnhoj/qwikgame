from django.urls import path

from authenticate.views import LoginView, LoginSentView, LoginHandleView, RegisterView, RegisterSentView, RegisterHandleView

urlpatterns = [
    # ex: /authenticate/
    path("login/", LoginView.as_view(), name="login"),
    path("login/sent/", LoginSentView.as_view(), name="login_sent"),
    path("login/<uidb64>/<token>/", LoginHandleView.as_view(), name="login_handle"),
    path("register/", RegisterView.as_view(), name="register"),
    path("register/sent/", RegisterSentView.as_view(), name="register_sent"),
    path("register/<uidb64>/<token>/", RegisterHandleView.as_view(), name="register_handle"),
]