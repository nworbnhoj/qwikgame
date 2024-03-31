from django.urls import include, path
from person.views import AccountView, PrivateView, PrivacyView, PublicView, UpgradeView

urlpatterns = [
    path("", AccountView.as_view(), name='account'),

    # manager/ & player/ both over-ride name=(privacy/private/public/upgrade)
    # hence must preceed privacy/ private/ public & upgrade/
    # https://docs.djangoproject.com/en/5.0/topics/http/urls/#naming-url-patterns
    path("manager/", include("venue.urls")),
    path("player/", include("player.urls")),

    # name
    path("privacy/", PrivacyView.as_view(), name='privacy'),
    path("private/", PrivateView.as_view(), name='private'),
    path("public/", PublicView.as_view(), name='public'),
    path("upgrade/", UpgradeView.as_view(), name='upgrade'),
]