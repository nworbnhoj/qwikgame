from django.contrib.auth.decorators import login_required
from django.utils.decorators import method_decorator
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from django.views import View
from authenticate.models import User
from person.models import Person
from person.forms import PrivateForm, PublicForm
from player.models import Player, Precis
from player.forms import BlockedForm, PrecisForm
from qwikgame.views import small_screen


class AccountView(View):

    def get(self, request):
        if small_screen(request.device):
            context = { "small_screen": True, 'big_screen': False }
            return render(request, "person/account.html", context)
        else:
            return HttpResponseRedirect("/account/public/")


class PrivacyView(View):

    def get(self, request):
        small = small_screen(request.device)
        context = {
            'big_screen': not small,
            'small_screen': small,
        }
        return render(request, "person/privacy.html", context)


class PrivateView(View):
    private_form_class = PrivateForm
    blocked_form_class = BlockedForm
    template_name = "person/private.html"

    @method_decorator(login_required)
    def dispatch(self, *args, **kwargs):
        return super().dispatch(*args, **kwargs)

    def get(self, request, *args, **kwargs):
        user = User.objects.get(pk=request.user.id)
        context = self.private_form_class.get(user.person)
        if hasattr(user, "player"):
            context = context | self.blocked_form_class.get(user.player)
        if hasattr(user, "manager"):
            manager = user.manager
            context = context | {}
        small = small_screen(request.device)
        context = context | {
            'big_screen': not small,
            "email": request.user.email,
            'small_screen': small,
        }
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        user = User.objects.get(pk=request.user.id)
        context = self.private_form_class.post(request.POST, user.person)
        if hasattr(user, "player"):
            context = context | self.blocked_form_class.post(request.POST, user.player)
        if len(context) == 0:
            return HttpResponseRedirect("/account/private/")
        small = small_screen(request.device)
        context = context | {
            'big_screen': not small,
            'small_screen': small,
        }
        return render(request, self.template_name, context)


class PublicView(View):
    public_form_class = PublicForm
    precis_form_class = PrecisForm
    template_name = "person/public.html"

    @method_decorator(login_required)
    def dispatch(self, *args, **kwargs):
        return super().dispatch(*args, **kwargs)

    def get(self, request, *args, **kwargs):
        user = User.objects.get(pk=request.user.id)
        context = self.public_form_class.get(user.person)
        if hasattr(user, "player"):
            context = context | self.precis_form_class.get(user.player)
        if hasattr(user, "manager"):
            manager = user.manager
            context = context | {}
        small = small_screen(request.device)
        context = context | {
            'big_screen': not small,
            'small_screen': small,
        }
        return render(request, self.template_name , context)

    def post(self, request, *args, **kwargs):
        user = User.objects.get(pk=request.user.id)
        context = self.public_form_class.post(request.POST, user.person)
        if hasattr(user, "player"):
            player = user.player
            context = context | self.precis_form_class.post(request.POST, player)
        if len(context) == 0:
            return HttpResponseRedirect("/account/public/")
        small = small_screen(request.device)
        context = context | {
            'big_screen': not small,
            'small_screen': small,
        }
        return render(request, self.template_name, context)


class UpgradeView(View):

    def get(self, request):
        small = small_screen(request.device)
        context = {
            'big_screen': not small,
            'small_screen': small,
        }
        return render(request, "person/upgrade.html", context)

