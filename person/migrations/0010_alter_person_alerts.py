# Generated by Django 5.0.2 on 2024-12-10 01:10

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('person', '0009_alert_person_alerts'),
    ]

    operations = [
        migrations.AlterField(
            model_name='person',
            name='alerts',
            field=models.JSONField(default=dict),
        ),
    ]
