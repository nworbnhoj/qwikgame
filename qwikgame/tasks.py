import logging
from celery import shared_task
from qwikgame.email_alert import EmailAlert


logger = logging.getLogger(__file__)


@shared_task(bind=True)
def send_email(self, to, from_email, subject, body):
    email = EmailAlert(
        to=to,
        from_email=from_email,
        subject=subject,
        body=body,
    )
    email.send()
    return "Done"