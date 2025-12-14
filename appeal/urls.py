from django.urls import include, path

from appeal.views import AcceptView, BidView, AppealsView, KeenView, BidView, RivalView
from qwikgame.views import ServiceWorkerView

urlpatterns = [
    path("", AppealsView.as_view(), name='appeal'),
    path("<int:appeal>/", BidView.as_view(), name='bid'),
    path("<int:appeal>/<rival>/", RivalView.as_view()),
    path("keen/", KeenView.as_view(), name='keen'),
    path("keen/<str:game>/", KeenView.as_view(), name='keen'),
    path("accept/<int:appeal>/", AcceptView.as_view(), name='accept'),
    path("accept/<int:appeal>/<rival>/", RivalView.as_view()),
    path('sw.js', ServiceWorkerView.as_view(), name='sw'),
]