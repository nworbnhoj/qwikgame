# Generated by Django 5.1.4 on 2025-01-31 23:14

import django.core.serializers.json
from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('person', '0014_alter_person_icon'),
    ]

    operations = [
        migrations.AlterField(
            model_name='person',
            name='language',
            field=models.CharField(choices=[('en', 'English')], default='en', max_length=2),
        ),
    ]
