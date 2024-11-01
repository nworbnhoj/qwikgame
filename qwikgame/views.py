import logging
from authenticate.models import User
from django.contrib.auth.decorators import login_required
from django.http import HttpResponse
from django.shortcuts import get_object_or_404, render
from django.utils.decorators import method_decorator
from django.views import View


logger = logging.getLogger(__file__)


class BaseView(View):
    _context = {}

    def get(self, request, *args, **kwargs):
        self.request_init(request)
        return None

    def post(self, request, *args, **kwargs):
        self.request_init(request)
        return None

    def request_init(self, request):
        pass

    def context(self, request, *args, **kwargs):
        small = self.small_screen(request.device)
        self._context = {
            'big_screen': not small,
            'small_screen': small,
        }
        return self._context

    def small_screen(self, device):
        if device.is_landscape and device.width >= 768:
            return False
        elif device.width >= 1024:
            return False
        else:
            return True



class QwikView(BaseView):
    is_player = False
    is_manager = False
    user = None

    @method_decorator(login_required)
    def dispatch(self, *args, **kwargs):
        return super().dispatch(*args, **kwargs)

    def request_init(self, request):
        super().request_init(request)
        self.user = User.objects.get(pk=request.user.id)
        self.is_player = hasattr(self.user, "player")
        self.is_manager = hasattr(self.user, "manager")

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        items = kwargs.get('items')
        item_pk = kwargs.get('pk')
        if items and items.first():
            if not item_pk:
                item_pk = items.first().pk
            prev_pk = items.last().pk
            next_pk = items.first().pk
            found = False
            for i in items:
                if found:
                    next_pk = i.pk
                    break
                if i.pk == item_pk:
                    found = True
                else:
                    prev_pk = i.pk
            self._context |= {
                'item': items.filter(pk=item_pk).first(),
                'next': next_pk,
                'prev': prev_pk,
            }
        self._context |= {
            'items': items,
            'person_icon': self.user.person.icon,
            'person_name': self.user.person.name,
        }
        return self._context



class WelcomeView(BaseView):

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        return render(request, "welcome.html", self._context)

