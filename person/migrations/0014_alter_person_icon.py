# Generated by Django 5.1.4 on 2025-01-31 23:11

import django.core.serializers.json
import person.models
from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('person', '0013_alter_person_name'),
    ]

    operations = [
        migrations.AlterField(
            model_name='person',
            name='icon',
            field=models.CharField(default=person.models.rnd_icon, max_length=32),
        ),
    ]
