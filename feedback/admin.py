from django.contrib import admin

from .models import Feedback

class FeedbackAdmin(admin.ModelAdmin):
    list_display = ['id', 'date', 'path', 'version', 'text']
    list_filter = ['date', 'path', 'version']
    ordering = ['date']
    readonly_fields = ('date', 'path', 'version')

admin.site.register(Feedback, FeedbackAdmin)
