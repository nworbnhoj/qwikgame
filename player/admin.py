from django import forms
from django.contrib import admin

from .models import Appeal, Bid, Filter, Friend, Opinion, Player, Precis, Strength


class AppealAdmin(admin.ModelAdmin):
    list_filter = ['game', 'venue__country', 'venue__admin1', 'venue__locality', 'venue']
    ordering = ['game']

class BidAdmin(admin.ModelAdmin):
    list_filter = ['appeal__game', 'appeal__venue__country', 'appeal__venue__admin1', 'appeal__venue__locality', 'appeal__venue']
    ordering = ['appeal__game']

class FilterAdmin(admin.ModelAdmin):
    list_filter = ['active', 'game', 'venue']
    ordering = ['game']

class FriendAdmin(admin.ModelAdmin):
    list_filter = ['player', 'rival']
    ordering = ['player']

class PlayerAdmin(admin.ModelAdmin):
    fields = ['user', 'email_hash', 'games', 'blocked']
    fieldsets = []
    filter_horizontal = []
    list_display = ['__str__']
    list_filter = ['games']
    search_fields = ['email', 'email_hash']
    ordering = ['user']


admin.site.register(Appeal, AppealAdmin)
admin.site.register(Bid, BidAdmin)
admin.site.register(Filter, FilterAdmin)
admin.site.register(Friend, FriendAdmin)
admin.site.register(Opinion)
admin.site.register(Player, PlayerAdmin)
admin.site.register(Precis)
admin.site.register(Strength)