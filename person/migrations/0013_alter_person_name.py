# Generated by Django 5.1.4 on 2025-01-31 23:08

import django.core.serializers.json
import person.models
from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('person', '0012_block_alter_person_block'),
    ]

    operations = [
        migrations.AlterField(
            model_name='person',
            name='name',
            field=models.CharField(default='my qwikname', max_length=32),
        ),
    ]
