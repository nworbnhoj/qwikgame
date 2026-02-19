from django.contrib import admin, auth
from django.urls import include, path
from django.views.generic.base import RedirectView
from django.views.generic.base import TemplateView

from qwikgame.views import LicenseView, OfflineView, PrivacyView, QwikView, ServiceWorkerView, TermsView, WelcomeView

urlpatterns = [
    path('', WelcomeView.as_view(), name='welcome'),
    path("about/license/", LicenseView.as_view(), name='license'),
    path("about/privacy/", PrivacyView.as_view(), name='privacy'),
    path("about/terms/", TermsView.as_view(), name='terms'),
    path('account/', include("person.urls")),
    path('admin/', admin.site.urls),
    path('appeal/', include('appeal.urls')),
    path('api/', include('api.urls')),
    path('authenticate/', include('authenticate.urls')),
    path('game/', include('game.urls')),
    path("feedback/", include("feedback.urls")),
    path('offline.html', OfflineView.as_view(), name='offline'),
    path('player/', include('player.urls')),
    path('venue/', include('venue.urls')),
    path('webpush/', include('webpush.urls')),
    path('webpush/icon/', RedirectView.as_view(url='/static/img/qwik-icon.svg')),
    # The service worker cannot be in /static because its scope will be limited to /static.
    path('sw.js', ServiceWorkerView.as_view(), name='sw'),
]
