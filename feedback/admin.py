from django.contrib import admin

from .models import Feedback

class FeedbackAdmin(admin.ModelAdmin):
    list_display = ['id', 'date', 'type', 'path', 'text', 'version']
    list_filter = ['date', 'path', 'type', 'version']
    ordering = ['date']
    readonly_fields = ('date', 'path', 'version')

admin.site.register(Feedback, FeedbackAdmin)
