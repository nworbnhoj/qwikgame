# Generated by Django 5.0.2 on 2024-10-08 04:30

from django.db import migrations


class Migration(migrations.Migration):

    dependencies = [
        ('venue', '0013_remove_region_admin1_remove_region_country_and_more'),
    ]

    operations = [
        migrations.RemoveField(
            model_name='venue',
            name='games',
        ),
        migrations.RemoveField(
            model_name='venue',
            name='managers',
        ),
    ]