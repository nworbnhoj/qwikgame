# Generated by Django 5.1.7 on 2025-04-11 00:03

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('person', '0022_alter_person_notify_email_alter_person_notify_web'),
    ]

    operations = [
        migrations.AlterField(
            model_name='person',
            name='notify_email',
            field=models.CharField(default='acfg', max_length=64),
        ),
        migrations.AlterField(
            model_name='person',
            name='notify_web',
            field=models.CharField(default='', max_length=64),
        ),
    ]
