import logging
from authenticate.models import User
from django.contrib.auth.decorators import login_required
from django.http import HttpResponse
from django.shortcuts import get_object_or_404, render
from django.templatetags.static import static
from django.utils.decorators import method_decorator
from django.views import View
from django.views.generic import TemplateView
from game.models import Game


logger = logging.getLogger(__file__)


class BaseView(View):

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
        context = {
            'account_alert': 'hidden',
            'appeal_alert': 'hidden',
            'friend_alert': 'hidden',
            'match_alert': 'hidden',
            'review_alert': 'hidden',
            'big_screen': not small,
            'small_screen': small,
        }
        return context

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
        context['item'] = None
        if items and items.first():
            item_pk = kwargs.get('pk')
            if item_pk:
                context['item'] = items.filter(pk=item_pk).first()
            else:
                item_pk = items.first()
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
            context |= {
                'next': next_pk,
                'prev': prev_pk,
            }
        person = self.user.person
        player = self.user.player
        context |= {
            'items': items,
            'person_icon': self.user.person.icon,
            'person_name': self.user.person.name,
            'appeal_alert': person.alert_show('appeal'),
            'match_alert': person.alert_show('match'),
            'review_alert': person.alert_show('review'),
            'friend_alert': person.alert_show('friend'),
            'account_alert': person.alert_show('acount'),
        }
        return context


class ServiceWorkerView(TemplateView):
    template_name = 'sw.js'
    content_type = 'application/javascript'
    name = 'sw.js'

    def get_context_data(self, **kwargs):
        return {
            'js_url': static('qwik.js'),
        }
class WelcomeView(BaseView):

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        context['games']: list(Game.objects.all())
        return context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        return render(request, "welcome.html", context)


