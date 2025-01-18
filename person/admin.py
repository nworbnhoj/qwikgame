from django.contrib import admin

from .models import Block, Person, Social

admin.site.register(Block)
admin.site.register(Person)
admin.site.register(Social)