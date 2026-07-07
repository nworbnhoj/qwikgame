from django.forms import ValidationError
from email_validator import validate_email as ev_validate, EmailNotValidError
from qwikgame.constants import ENDIAN


def int_to_hours24(integer):
    return integer.to_bytes(3, ENDIAN)


def str_to_hours24(string):
    return int_to_hours24(int(string))


def validate_email_deliverability(value):
    try:
        ev_validate(value, check_deliverability=True)
    except EmailNotValidError as e:
        raise ValidationError(str(e))
