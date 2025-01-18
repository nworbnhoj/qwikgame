from django.contrib import admin
from .models import Block, Person, Social


class BlockAdmin(admin.ModelAdmin):
    list_display = ['pk', 'person', 'blocked']
    list_filter = ['person', 'blocked']


admin.site.register(Block, BlockAdmin)
admin.site.register(Person)
admin.site.register(Social)