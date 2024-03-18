from django.db import models


class Game(models.Model):
    code = models.CharField(max_length=3, primary_key=True)
    name = models.CharField(max_length=32)

    def __str__(self):
        return self.name

