# Generated by Django 5.1.7 on 2025-04-10 23:15

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('person', '0021_alter_alert_type'),
    ]

    operations = [
        migrations.AlterField(
            model_name='person',
            name='notify_email',
            field=models.CharField(default='acfg', max_length=256),
        ),
        migrations.AlterField(
            model_name='person',
            name='notify_web',
            field=models.CharField(default='', max_length=256),
        ),
    ]
