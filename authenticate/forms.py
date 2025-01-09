import logging
from django.contrib.auth.forms import PasswordResetForm
from django.core.validators import ValidationError
from django.forms import CharField, HiddenInput


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
