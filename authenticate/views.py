import logging
from django import forms
from django.contrib.auth import login, logout
from django.contrib.auth.forms import PasswordResetForm
from django.contrib.auth.tokens import default_token_generator
from django.contrib.auth.forms import UserCreationForm
from django.contrib.auth.views import PasswordResetConfirmView
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, render
from django.urls import reverse, reverse_lazy
from django.utils.decorators import method_decorator
from django.views.decorators.cache import never_cache
from django.views.decorators.csrf import csrf_protect
from django.views.generic import CreateView
from django.views.decorators.debug import sensitive_post_parameters
from django.views.generic.base import TemplateView
from django.views.generic.edit import FormView
from authenticate.forms import RegisterForm, LoginForm
from person.models import Person


logger = logging.getLogger(__file__)


class EmailValidateView(FormView):
    email_template_name = "authenticate/email_validate_email.html"
    extra_email_context = None
    form_class = LoginForm
    from_email = "accounts@qwikgame.org"
    to_email = None
    html_email_template_name = None
    subject_template_name = "authenticate/email_validate_subject.txt"
    success_url = reverse_lazy("email_validate_done")
    template_name = "authenticate/email_validate_form.html"
    title = "Email validate"
    token_generator = default_token_generator # see PASSWORD_RESET_TIMEOUT

    @method_decorator(csrf_protect)
    def dispatch(self, *args, **kwargs):
        return super().dispatch(*args, **kwargs)

    def get_success_url(self):
        url = super().get_success_url()
        return f'{url}?to_email={self.to_email}'

    def form_valid(self, form):
        opts = {
            "use_https": self.request.is_secure(),
            "token_generator": self.token_generator,
            "from_email": self.from_email,
            "email_template_name": self.email_template_name,
            "subject_template_name": self.subject_template_name,
            "request": self.request,
            "html_email_template_name": self.html_email_template_name,
            "extra_email_context": self.extra_email_context,
        }
        form.save(**opts)
        self.to_email = form.cleaned_data['email']
        return super().form_valid(form)


class EmailValidateDoneView(TemplateView):
    template_name = "authenticate/email_validate_done.html"
    title = "Account details sent"


HOUR_SECONDS = 60 * 60
DAY_SECONDS = 24 * HOUR_SECONDS

class EmailValidateHandleView(PasswordResetConfirmView):
    fail_url = reverse_lazy('welcome')
    session_time = 7 * DAY_SECONDS
    success_url = reverse_lazy("appeal")
    token_generator = default_token_generator # see PASSWORD_RESET_TIMEOUT
    token_invalid_url = reverse_lazy('token_invalid')

    def prep_user(self):
        self.request.session.set_expiry(self.session_time)
        return True

    @method_decorator(sensitive_post_parameters())
    @method_decorator(never_cache)
    def dispatch(self, request, *args, **kwargs):
        if "uidb64" not in kwargs or "token" not in kwargs:
            raise ImproperlyConfigured(
                "The URL path must contain 'uidb64' and 'token' parameters."
            )
        self.validlink = False
        self.user = self.get_user(kwargs["uidb64"])
        if self.user is not None:
            token = kwargs["token"]
            if self.token_generator.check_token(self.user, token):
                self.validlink = True
                if self.prep_user():
                    login(self.request, self.user)
                    url = request.GET.get('next', self.get_success_url())
                    return HttpResponseRedirect(url)
            else:
                logout(self.request)
                return HttpResponseRedirect(self.token_invalid_url)
        logout(self.request)
        return HttpResponseRedirect(self.fail_url)


class LoginView(EmailValidateView):
    email_template_name = "authenticate/login_email_text.html"
    html_email_template_name = "authenticate/login_email_html.html"
    subject_template_name = "authenticate/login_email_subject.txt"
    success_url = reverse_lazy("login_sent")
    template_name = "authenticate/login_form.html"
    title = "Login"


class LoginSentView(TemplateView):
    template_name = "authenticate/login_email_sent.html"
    title = "Login details sent"


class LoginHandleView(EmailValidateHandleView):

    def dispatch(self, *args, **kwargs):
        return super().dispatch(*args, **kwargs)


class RegisterView(EmailValidateView):
    email_template_name = "authenticate/register_email_text.html"
    html_email_template_name = "authenticate/register_email_html.html"
    form_class = RegisterForm
    subject_template_name = "authenticate/register_email_subject.txt"
    success_url = reverse_lazy("register_sent")
    template_name = "authenticate/register_form.html"
    title = "Register"
    

class RegisterSentView(TemplateView):
    template_name = "authenticate/register_email_sent.html"
    title = "Register details sent"


class RegisterHandleView(EmailValidateHandleView):

    def dispatch(self, *args, **kwargs):
        return super().dispatch(*args, **kwargs)

    def prep_user(self):
        from player.views import Player
        person, created = Person.objects.get_or_create(
            user=self.user
        )
        person.user = self.user
        person.save()
        if created:
            logger.info(f'created Person: {person.pk}')
        else:
            logger.info(f'linked existing Person to user: {person.pk}')
        player, created = Player.objects.get_or_create(
            email_hash=Person.hash(self.user.email)
        )
        if created:
            logger.info(f'created Player: {player.pk}')
        else:
            logger.info(f'linked existing Player to user: {player.pk}')
        player.user = self.user
        player.save()
        return super().prep_user()


class TokenInvalidView(TemplateView):
    template_name = "authenticate/token_invalid.html"
    title = "Invalid token"
