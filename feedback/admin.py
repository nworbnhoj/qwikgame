from django.contrib import admin

from .models import Feedback

class FeedbackAdmin(admin.ModelAdmin):
    list_display = ['id', 'date', 'text']
    list_filter = ['date']
    ordering = ['date']

admin.site.register(Feedback, FeedbackAdmin)
