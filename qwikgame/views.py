from authenticate.models import User
from django.contrib.auth.decorators import login_required
from django.http import HttpResponse
from django.shortcuts import get_object_or_404, render
from django.utils.decorators import method_decorator
from django.views import View


class QwikView(View):
    context = {}
    user = None
    is_player = False
    is_player = False

    @method_decorator(login_required)
    def dispatch(self, *args, **kwargs):
        return super().dispatch(*args, **kwargs)

    def get(self, request, *args, **kwargs):
        self.request_init(request)
        return None

    def post(self, request, *args, **kwargs):
        self.request_init(request)
        return None

    def request_init(self, request):
        self.user = User.objects.get(pk=request.user.id)
        self.is_player = hasattr(self.user, "player")
        self.is_manager = hasattr(self.user, "manager")

    def context(self, request):
        small = self.small_screen(request.device)
        self.context = {
            'big_screen': not small,
            'person_icon': self.user.person.icon,
            'person_name': self.user.person.name,
            'small_screen': small,
        }
        return self.context

    def small_screen(self, device):
        if device.is_landscape and device.width >= 768:
            return False
        elif device.width >= 1024:
            return False
        else:
            return True