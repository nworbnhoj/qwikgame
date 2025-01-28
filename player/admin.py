from django import forms
from django.contrib import admin

from .models import Filter, Friend, Player, Strength

class FilterAdmin(admin.ModelAdmin):
    list_display = ['pk', 'active', 'player', 'place', 'game']
    list_filter = ['active', 'player', 'place', 'game']
    ordering = ['game']

class FriendAdmin(admin.ModelAdmin):
    list_filter = ['player', 'rival']
    ordering = ['player']

class PlayerAdmin(admin.ModelAdmin):
    fields = ['user', 'email_hash', 'games']
    fieldsets = []
    filter_horizontal = []
    list_display = ['__str__', 'conduct_stars', 'conduct_dips']
    list_filter = ['games']
    search_fields = ['email', 'email_hash']
    ordering = ['user']

class StrengthAdmin(admin.ModelAdmin):
    fields = ['game', 'player', 'rival', 'relative', 'date', 'weight']
    fieldsets = []
    filter_horizontal = []
    list_display = ['game', 'player', 'rival', 'relative']
    list_filter = ['game', 'player', 'rival', 'relative']
    search_fields = []
    ordering = []


admin.site.register(Filter, FilterAdmin)
admin.site.register(Friend, FriendAdmin)
admin.site.register(Player, PlayerAdmin)
admin.site.register(Strength, StrengthAdmin)