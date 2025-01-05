from django.contrib import admin, auth
from django.urls import include, path
from django.views.generic.base import TemplateView

from qwikgame.views import QwikView, WelcomeView

urlpatterns = [
    path('', WelcomeView.as_view(), name='welcome'),
    path('account/', include("person.urls")),
    path('admin/', admin.site.urls),
    path('api/', include('api.urls')),
    path('authenticate/', include('authenticate.urls')),
    path('authenticate/', include('django.contrib.auth.urls')),
    path('game/', include('game.urls')),
    path('player/', include('player.urls')),
    path('venue/', include('venue.urls')),
]
