from django.contrib import admin, auth
from django.urls import include, path

from qwikgame.views import QwikView, WelcomeView

urlpatterns = [
    path("", WelcomeView.as_view(), name='home'),
    path('account/', include("person.urls")),
    path("accounts/", include("django.contrib.auth.urls")),
    path('admin/', admin.site.urls),
    path('api/', include("api.urls")),
    path('authenticate/', include("authenticate.urls")),
    path('game/', include("game.urls")),
    path('player/', include('player.urls')),
    path('venue/', include('venue.urls')),
]
