# Generated by Django 5.0.2 on 2024-03-12 04:36

import django.db.models.deletion
from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('player', '0003_player_blocked_player_icon_player_location_auto_and_more'),
    ]

    operations = [
        migrations.CreateModel(
            name='Friend',
            fields=[
                ('id', models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('email', models.EmailField(max_length=255, unique=True, verbose_name='email address')),
                ('name', models.CharField(blank=True, max_length=32)),
                ('player', models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, to='player.player')),
                ('rival', models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, to='player.friend')),
            ],
        ),
        migrations.AddField(
            model_name='player',
            name='friends',
            field=models.ManyToManyField(blank=True, through='player.Friend', to='player.player'),
        ),
    ]