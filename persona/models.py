from django.db import models

from authenticate.models import User


class Persona(models.Model):
    icon = models.CharField(max_length=16)
    name = models.CharField(max_length=32)
    user = models.OneToOneField(User, on_delete=models.CASCADE)

    def __str__(self):
    	return self.name


class Social(models.Model):
    persona = models.ForeignKey(Persona, on_delete=models.CASCADE)
    url = models.URLField(max_length=255)

    def __str__(self):
        return self.url