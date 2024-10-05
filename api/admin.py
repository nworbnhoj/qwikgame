from django.contrib import admin

from .models import Mark, Region


class MarkAdmin(admin.ModelAdmin):
    list_filter = ['game', 'region__country', 'region__admin1', 'region__locality']
    ordering = ['game', 'region__country', 'region__admin1', 'region__locality']

class RegionAdmin(admin.ModelAdmin):
    list_filter = ['country', 'admin1', 'locality']
    ordering = ['country', 'admin1', 'locality']


admin.site.register(Mark, MarkAdmin)
admin.site.register(Region, RegionAdmin)