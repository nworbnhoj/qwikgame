
from django import forms
from django.contrib.auth.decorators import login_not_required, login_required
from django.contrib.auth.forms import PasswordResetForm
from django.contrib.auth.tokens import default_token_generator
from django.contrib.auth.forms import UserCreationForm
from django.http import HttpResponse
from django.shortcuts import get_object_or_404, render
from django.urls import reverse_lazy
from django.utils.decorators import method_decorator
from django.views.decorators.csrf import csrf_protect
from django.views.generic import CreateView
from django.views.generic.base import TemplateView
from django.views.generic.edit import FormView


@method_decorator(login_not_required, name="dispatch")
class EmailValidateView(FormView):
    email_template_name = "registration/email_validate_email.html"
    extra_email_context = None
    form_class = PasswordResetForm
    from_email = None
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


@method_decorator(login_not_required, name="dispatch")
class EmailValidateDoneView(TemplateView):
    template_name = "registration/email_validate_done.html"
    title = "Login details sent"


def index(request):
    return HttpResponse("authentication form placeholder.")


class SignUpView(CreateView):
    form_class = UserCreationForm
    success_url = reverse_lazy("login")
    template_name = "registration/signup.html"