from django import forms
from django.contrib import admin
from appeal.models import Appeal, Bid

class AppealAdmin(admin.ModelAdmin):
    list_display = ['pk', 'player', 'game', 'date', 'hour_dips', 'venue']
    list_filter = ['game', 'venue__country', 'venue__admin1', 'venue__locality', 'venue']
    ordering = ['game']

class BidAdmin(admin.ModelAdmin):
    list_display = ['pk', 'rival', '_hour', 'strength', 'appeal']
    list_filter = ['appeal__game', 'appeal__venue__country', 'appeal__venue__admin1', 'appeal__venue__locality', 'appeal__venue']
    ordering = ['appeal__game']

    
admin.site.register(Appeal, AppealAdmin)
admin.site.register(Bid, BidAdmin)