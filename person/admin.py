from django.contrib import admin
from .models import Block, Person, Social


class BlockAdmin(admin.ModelAdmin):
    list_display = ['pk', 'person', 'blocked']
    list_filter = ['person', 'blocked']


class PersonAdmin(admin.ModelAdmin):
    list_display = ['name', 'icon', 'language', 'notify_email', 'notify_push', 'user']
    list_filter = ['icon', 'language', 'notify_email', 'notify_push']


admin.site.register(Block, BlockAdmin)
admin.site.register(Person, PersonAdmin)
admin.site.register(Social)