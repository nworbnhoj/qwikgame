# Generated by Django 5.0.2 on 2024-10-09 07:48

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('venue', '0016_venue_games_venue_managers'),
    ]

    operations = [
        migrations.AddConstraint(
            model_name='place',
            constraint=models.UniqueConstraint(fields=('placeid',), name='unique_placeid'),
        ),
    ]
