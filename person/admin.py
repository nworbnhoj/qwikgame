from django.contrib import admin

from .models import Person, Social

admin.site.register(Person)
admin.site.register(Social)