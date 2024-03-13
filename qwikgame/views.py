from django.http import HttpResponse
from django.shortcuts import get_object_or_404, render
from django.views import View


class QwikView(View):

    def get(self, request):
        return render(request, "qwik.html")