# Generated by Django 5.0.2 on 2024-04-04 19:05

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('venue', '0006_remove_manager_location_auto_and_more'),
    ]

    operations = [
        migrations.AlterField(
            model_name='venue',
            name='managers',
            field=models.ManyToManyField(blank=True, to='venue.manager'),
        ),
    ]
