import logging
from django.contrib.auth.forms import PasswordResetForm


logger = logging.getLogger(__file__)


class EmailValidateForm(PasswordResetForm):

	def __init__(self, *args, **kwargs):
		super().__init__(*args, **kwargs)
		self.fields['email'].widget.attrs['placeholder'] = 'Email address'
