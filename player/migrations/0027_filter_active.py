# Generated by Django 5.0.2 on 2024-09-07 02:13

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('player', '0026_filter_filter_unique_filter'),
    ]

    operations = [
        migrations.AddField(
            model_name='filter',
            name='active',
            field=models.BooleanField(default=True),
        ),
    ]