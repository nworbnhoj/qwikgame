import logging, string
from django.contrib.auth.forms import PasswordResetForm
from django.core.validators import ValidationError
from django.forms import CharField, HiddenInput
from django.utils.crypto import get_random_string
from authenticate.models import User


logger = logging.getLogger(__file__)


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
            raise ValidationError("bot")
        return None


class RegisterForm(EmailValidateForm):

    def rnd_pwd(self):
        return get_random_string(
                20,
                allowed_chars=string.ascii_uppercase + string.digits
            )

    def get_users(self, email):
        logger.warn("get_users()")
        """Override to create a preliminary user for this email"""
        user, created = User.objects.get_or_create(email=email)
        logger.warn(user)
        if created:
            user.set_password(self.rnd_pwd())
            user.save(update_fields=['password'])
            logger.info(f'Created User: {user.pk}')
        else:
            logger.warn(f'Register User aborted: {user.pk} exists')
            return ()
        return ( user, )