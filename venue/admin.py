from django.contrib import admin

from .models import Manager, Place, Region, Venue


class RegionAdmin(admin.ModelAdmin):
    list_filter = ['country', 'admin1', 'locality']
    ordering = ['country', 'admin1', 'locality']


class VenueAdmin(admin.ModelAdmin):
    list_display = ['name', 'open_week']
    list_filter = ['games', 'country', 'admin1', 'locality']
    ordering = ['name']


admin.site.register(Manager)
admin.site.register(Place)
admin.site.register(Region, RegionAdmin)
admin.site.register(Venue, VenueAdmin)