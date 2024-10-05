from django.contrib import admin

from .models import Manager, Venue


class VenueAdmin(admin.ModelAdmin):
    list_filter = ['games', 'country', 'admin1', 'locality']
    ordering = ['name']


admin.site.register(Manager)
admin.site.register(Venue, VenueAdmin)