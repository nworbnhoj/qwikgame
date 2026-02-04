from django.contrib import admin
from .models import Alert, Block, Person, Social


class AlertAdmin(admin.ModelAdmin):
    list_display = ['pk', 'person', 'mode', 'type', 'priority', 'expires', 'repeats']
    list_filter = ['mode', 'type', 'priority', 'expires', 'repeats', 'person']


class BlockAdmin(admin.ModelAdmin):
    list_display = ['pk', 'person', 'blocked']
    list_filter = ['person', 'blocked']


class PersonAdmin(admin.ModelAdmin):
    list_display = ['name', 'language', 'notify_email', 'notify_push', 'user']
    list_filter = ['language', 'notify_email', 'notify_push']


admin.site.register(Alert, AlertAdmin)
admin.site.register(Block, BlockAdmin)
admin.site.register(Person, PersonAdmin)
admin.site.register(Social)