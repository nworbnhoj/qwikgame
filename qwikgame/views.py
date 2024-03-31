from django.http import HttpResponse
from django.shortcuts import get_object_or_404, render
from django.views import View


class QwikView(View):

    def get(self, request):
        return render(request, "qwik.html")

def small_screen(device):
    if device.is_landscape and device.width >= 768:
        return False
    elif device.width >= 1024:
        return False
    else:
        return True