# Generated by Django 5.0.2 on 2024-03-18 19:16

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('person', '0007_person_location_auto_person_notify_email_and_more'),
    ]

    operations = [
        migrations.AddField(
            model_name='person',
            name='language',
            field=models.CharField(choices=[('bg', 'български'), ('en', 'English'), ('es', 'Español'), ('zh', '中文'), ('ru', 'русский'), ('fr', 'Français'), ('hi', 'हिंदी'), ('ar', 'اللغة العربية'), ('jp', '日本語')], default='en', max_length=2),
        ),
    ]