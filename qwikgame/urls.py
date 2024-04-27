from django.contrib import admin
from django.urls import include, path

from qwikgame.views import QwikView

urlpatterns = [
    path("", QwikView.as_view(), name='home'),
    path('account/', include("person.urls")),
    path('admin/', admin.site.urls),
    path('authenticate/', include("authenticate.urls")),
    path('game/', include("game.urls")),
    path('player/', include('player.urls'))
]
