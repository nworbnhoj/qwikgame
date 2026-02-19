import logging
from person.models import AlertEmail

# intended to be run every minute as a cron job
def smtp_connection_close():
    if AlertEmail.timeout:
        logger.info(f'CRON: smtp_connection_close() SMTP connection timed-out and closed')