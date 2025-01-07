import logging
from django import forms
from django.contrib.auth import login as auth_login
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


logger = logging.getLogger(__file__)


class EmailValidateView(FormView):
    email_template_name = "registration/email_validate_email.html"
    extra_email_context = None
    form_class = PasswordResetForm
    from_email = "accounts@qwikgame.org"
    html_email_template_name = None
    subject_template_name = "registration/email_validate_subject.txt"
    success_url = reverse_lazy("email_validate_done")
    template_name = "registration/email_validate_form.html"
    title = "Email validate"
    token_generator = default_token_generator

    @method_decorator(csrf_protect)
    def dispatch(self, *args, **kwargs):
        return super().dispatch(*args, **kwargs)

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
        return super().form_valid(form)


class EmailValidateDoneView(TemplateView):
    template_name = "registration/email_validate_done.html"
    title = "Account details sent"


HOUR_SECONDS = 60 * 60
DAY_SECONDS = 24 * HOUR_SECONDS

class EmailValidationHandleView(PasswordResetConfirmView):
    fail_url = reverse_lazy('welcome')
    session_time = 7 * DAY_SECONDS
    success_url = reverse_lazy("appeal")
    token_generator = default_token_generator

    @method_decorator(sensitive_post_parameters())
    @method_decorator(never_cache)
    def dispatch(self, *args, **kwargs):
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
                self.request.session.set_expiry(self.session_time)
                auth_login(self.request, self.user)
                return HttpResponseRedirect(self.get_success_url())
        return HttpResponseRedirect(self.fail_url)


class LoginView(EmailValidateView):
    subject_template_name = "registration/email_login_subject.txt"
    success_url = reverse_lazy("login_sent")
    template_name = "registration/login_form.html"
    title = "Login"


class LoginSentView(TemplateView):
    template_name = "registration/email_login_sent.html"
    title = "Login details sent"


class RegisterView(EmailValidateView):
    subject_template_name = "registration/email_register_subject.txt"
    success_url = reverse_lazy("register_sent")
    template_name = "registration/register_form.html"
    title = "Register"
    

class RegisterSentView(TemplateView):
    template_name = "registration/email_register_sent.html"
    title = "Register details sent"