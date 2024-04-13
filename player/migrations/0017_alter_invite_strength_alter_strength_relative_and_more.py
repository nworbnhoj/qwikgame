# Generated by Django 5.0.2 on 2024-04-13 01:05

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('game', '0003_alter_game_icon'),
        ('player', '0016_alter_precis_text'),
        ('venue', '0007_alter_venue_managers'),
    ]

    operations = [
        migrations.AlterField(
            model_name='invite',
            name='strength',
            field=models.CharField(choices=[('W', 'much-weaker'), ('w', 'weaker'), ('m', 'matched'), ('s', 'stronger'), ('S', 'much-stonger')], max_length=1),
        ),
        migrations.AlterField(
            model_name='strength',
            name='relative',
            field=models.CharField(choices=[('W', 'much-weaker'), ('w', 'weaker'), ('m', 'matched'), ('s', 'stronger'), ('S', 'much-stonger')], max_length=1),
        ),
        migrations.AlterUniqueTogether(
            name='available',
            unique_together={('game', 'player', 'venue')},
        ),
    ]
