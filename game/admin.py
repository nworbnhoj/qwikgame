from django import forms
from django.contrib import admin

from .models import Game, Match, Review

class MatchAdmin(admin.ModelAdmin):
    list_display = ['pk', 'game', 'datetime_str', 'venue', 'competitor_names']
    list_filter = [ 'game', 'date', 'venue__country', 'venue__admin1', 'venue__locality', 'venue', 'competitors' ]
    ordering = ['date', 'game']

admin.site.register(Game)
admin.site.register(Match, MatchAdmin)
admin.site.register(Review)