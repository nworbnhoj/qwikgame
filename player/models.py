from django.db import models

from authenticate.models import User


class Player(models.Model):
    email_hash = models.CharField(max_length=32, primary_key=True)
    user = models.OneToOneField(User, on_delete=models.CASCADE)

    def __str__(self):
        return self.user
