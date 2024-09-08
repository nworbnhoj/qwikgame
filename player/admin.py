from django import forms
from django.contrib import admin

from .models import Appeal, Filter, Friend, Invite, Opinion, Player, Precis, Strength


class PlayerAdmin(admin.ModelAdmin):
    fields = ['user', 'email_hash', 'games', 'blocked']
    fieldsets = []
    filter_horizontal = []
    list_display = ['__str__']
    list_filter = ['games']
    search_fields = ['email', 'emai_hash']
    ordering = ['user']


admin.site.register(Appeal)
admin.site.register(Filter)
admin.site.register(Friend)
admin.site.register(Invite)
admin.site.register(Opinion)
admin.site.register(Player, PlayerAdmin)
admin.site.register(Precis)
admin.site.register(Strength)