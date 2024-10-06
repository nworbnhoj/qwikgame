from django.contrib import admin

from .models import Mark


class MarkAdmin(admin.ModelAdmin):
    list_filter = ['game', 'region__country', 'region__admin1', 'region__locality']
    ordering = ['game', 'region__country', 'region__admin1', 'region__locality']


admin.site.register(Mark, MarkAdmin)