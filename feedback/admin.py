from django.contrib import admin

from .models import Feedback

class FeedbackAdmin(admin.ModelAdmin):
    list_display = ['id', 'date', 'text']
    list_filter = ['date']
    ordering = ['date']
    readonly_fields = ('date', 'path')

admin.site.register(Feedback, FeedbackAdmin)
