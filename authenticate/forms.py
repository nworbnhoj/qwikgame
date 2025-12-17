import logging, string
from django.contrib.auth.forms import PasswordResetForm
from django.core.mail import EmailMultiAlternatives, get_connection
from django.core.validators import ValidationError
from django.forms import CharField, HiddenInput
from django.template import loader
from django.utils.crypto import get_random_string
from django.utils.safestring import mark_safe
from authenticate.models import User
from qwikgame.settings import EMAIL_ACCOUNT_USER, EMAIL_ACCOUNT_PASSWORD


logger = logging.getLogger(__file__)

class AccountEmail(EmailMultiAlternatives):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.connection = get_connection(
            fail_silently=False,
            username = EMAIL_ACCOUNT_USER,
            password = EMAIL_ACCOUNT_PASSWORD,
        )


class EmailValidateForm(PasswordResetForm):
    # TODO refine the honeypot

    # honeypot
    password = CharField(
        required=False,
        widget=HiddenInput(),
        )

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields['email'].widget.attrs['placeholder'] = 'Email address'

    def clean_password(self):
        honeypot = self.cleaned_data['password']
        if len(honeypot) > 0:
            logger.warn(f"honeypot filled with: [{honeypot}]")
            raise ValidationError("Password incorrect")
        return None

    # over-ride with an exact copy of super, but replacing EmailMultiAlternatives
    def send_mail(
        self,
        subject_template_name,
        email_template_name,
        context,
        from_email,
        to_email,
        html_email_template_name=None,
    ):
        """
        Send a django.core.mail.EmailMultiAlternatives to `to_email`.
        """
        subject = loader.render_to_string(subject_template_name, context)
        # Email subject *must not* contain newlines
        subject = "".join(subject.splitlines())
        body = loader.render_to_string(email_template_name, context)

        email_message = AccountEmail(subject, body, from_email, [to_email])
        if html_email_template_name is not None:
            html_email = loader.render_to_string(html_email_template_name, context)
            email_message.attach_alternative(html_email, "text/html")

        try:
            email_message.send()
        except Exception:
            logger.exception(
                "Failed to send password reset email to %s", context["user"].pk
            )


class LoginForm(EmailValidateForm):

    def clean_email(self):
        email = self.cleaned_data['email']
        try:
            user = User.objects.get(email=email)
            if user.has_usable_password():
                return email
            else:
                raise ValidationError(f'The account is disabled for {email}')
        except User.DoesNotExist:
            raise ValidationError((mark_safe(f"There is no account for this email.<br>Would you like to <a href='/authenticate/register/'>register</a>?")))


class RegisterForm(EmailValidateForm):

    def rnd_pwd(self):
        return get_random_string(
                20,
                allowed_chars=string.ascii_uppercase + string.digits
            )

    def get_users(self, email):
        """Override to create a preliminary user for this email"""
        user, created = User.objects.get_or_create(email=email)
        if created:
            user.set_password(self.rnd_pwd())
            user.save(update_fields=['password'])
            logger.info(f'Created User: {user.pk}')
        return ( user, )