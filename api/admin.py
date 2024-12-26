from django.contrib import admin

from .models import Mark


class MarkAdmin(admin.ModelAdmin):
    list_display = ['game', 'place', 'num_player', 'num_venue']
    list_filter = ['game', 'place__country', 'place__admin1', 'place__locality']
    ordering = ['game', 'place__country', 'place__admin1', 'place__locality']


admin.site.register(Mark, MarkAdmin)