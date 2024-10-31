from django.contrib import admin

from .models import Game, Match, Review

admin.site.register(Game)
admin.site.register(Match)
admin.site.register(Review)