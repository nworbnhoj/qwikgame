from django.urls import path

from feedback.views import FeedbackView, FeedbackListView

urlpatterns = [
    path("", FeedbackListView.as_view(), name='feedback'),
    path("<int:feedback>/", FeedbackView.as_view(), name='feedback'),
    path('sw.js', ServiceWorkerView.as_view(), name='sw'),
]