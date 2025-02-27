# Generated by Django 5.0.2 on 2024-03-12 22:16

import django.db.models.deletion
from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('game', '0001_initial'),
        ('player', '0010_strength'),
        ('venue', '0002_venue_games'),
    ]

    operations = [
        migrations.CreateModel(
            name='Available',
            fields=[
                ('id', models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('week', models.BinaryField()),
                ('game', models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, to='game.game')),
                ('player', models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, to='player.player')),
                ('venue', models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, to='venue.venue')),
            ],
        ),
    ]
