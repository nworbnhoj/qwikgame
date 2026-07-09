import logging
from datetime import datetime, timedelta
from django.core.mail import send_mail
from qwikgame.settings import EMAIL_ALERT_NAME, EMAIL_ALERT_PASSWORD, EMAIL_ALERT_USER, EMAIL_SMTP_TIMEOUT
from django.core.mail import EmailMultiAlternatives, get_connection


logger = logging.getLogger(__file__)


class EmailAlert(EmailMultiAlternatives):

    _open_connection = None
    _timeout_connection = datetime.now()

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.get_connection()
        self.from_email = "{}<{}>".format(EMAIL_ALERT_NAME, EMAIL_ALERT_USER)

    def get_connection(self, fail_silently=False):
        if EmailAlert._open_connection:
            self.connection = EmailAlert._open_connection
        else:
            self.connection = get_connection(
                username=EMAIL_ALERT_USER,
                password=EMAIL_ALERT_PASSWORD,
            )
            if self.connection.open():
                EmailAlert._open_connection = self.connection
                logger.info(f'SMTP connection opened')
            else:
                logger.warn(f'SMTP connection open failed')
        EmailAlert.prolong_connection()
        return self.connection

    @classmethod
    def prolong_connection(klass):
        extention = timedelta(seconds=EMAIL_SMTP_TIMEOUT)
        EmailAlert._timeout_connection = datetime.now() + extention

    @classmethod
    @property
    def timeout(klass):
        connection = EmailAlert._open_connection
        if connection and now() > EmailAlert._timeout_connection:
            EmailAlert._open_connection = None
            connection.close()
            logger.info(f'SMTP connection closed')
            return True
        return False
