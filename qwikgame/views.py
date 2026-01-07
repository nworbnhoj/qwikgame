import logging, subprocess
from authenticate.models import User
from django.contrib.auth.decorators import login_required
from django.http import HttpResponse
from django.shortcuts import get_object_or_404, render
from django.templatetags.static import static
from django.urls import reverse
from django.utils.decorators import method_decorator
from django.views import View
from django.views.generic import TemplateView
from feedback.forms import FeedbackForm
from game.models import Game


logger = logging.getLogger(__file__)


class BaseView(View):
    feedback_form_class = FeedbackForm

    def get(self, request, *args, **kwargs):
        self.request_init(request)
        return None

    def post(self, request, *args, **kwargs):
        self.request_init(request)
        return None

    def request_init(self, request):
        if User.objects.filter(pk=request.user.id).exists():
            self.user = User.objects.get(pk=request.user.id)
        return None

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
        context |= self.feedback_form_class.get()
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
        if hasattr(self, 'user'):   
            self.is_player = hasattr(self.user, "player")
            self.is_manager = hasattr(self.user, "manager")

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        if not hasattr(self, 'user'):
            return {}
        person = self.user.person
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
        context |= {
            'items': items,
            'person_icon': person.icon,
            'qwikname': person.qwikname,
            'appeal_alert': person.alert_show('abklm'),
            'match_alert': person.alert_show('pqr'),
            'review_alert': person.alert_show(''),
            'friend_alert': person.alert_show(''),
            'account_alert': person.alert_show(''),
        }
        return context


class ServiceWorkerView(TemplateView):
    template_name = 'sw.js'
    content_type = 'application/javascript'
    name = 'sw.js'

    def get_context_data(self, **kwargs):
        return {
            'css_all_min_url': static('css/all.min.css'),
            'css_map_url': static('css/map.css'),
            'css_qwik_url': static('css/qwik.css'),
            'css_reset_url': static('css/reset.css'),
            'css_small_screen_url': static('css/small_screen.css'),
            'favicon_url': static('img/favicon.ico'),
            'font_fa_url': static('font/fa-solid-900.woff2'),
            'font_notosans_url': static('font/NotoSans-Regular.ttf'),
            'icon_url': static('img/qwik-icon.png'),
            'icon_152_url': static('img/qwik-icon.152x152.png'),
            'icon_192_url': static('img/qwik-icon.192x192.png'),
            'icon_512_url': static('img/qwik-icon.512x512.png'),
            'js_map_url': static('map.js'),
            'js_qwik_url': static('qwik.js'),
            'logo_url': static('img/qwik-logo.png'),
            'logo_152_url': static('img/qwik-logo.152x152.png'),
            'logo_192_url': static('img/qwik-logo.192x192.png'),
            'logo_512_url': static('img/qwik-logo.512x512.png'),
            'manifest_url': static('manifest.json'),
            'version': subprocess.check_output(["git", "describe", "--always"]).strip().decode(),
        }


class WelcomeView(BaseView):

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        context['games']: list(Game.objects.all())
        if hasattr(self, 'user'):   
            context['person_icon'] = self.user.person.icon
            context['qwikname'] = self.user.person.qwikname
        return context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        return render(request, "welcome.html", context)


