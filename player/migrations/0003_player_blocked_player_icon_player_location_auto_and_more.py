# Generated by Django 5.0.2 on 2024-03-12 04:33

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('player', '0002_player_games'),
    ]

    operations = [
        migrations.AddField(
            model_name='player',
            name='blocked',
            field=models.ManyToManyField(blank=True, to='player.player'),
        ),
        migrations.AddField(
            model_name='player',
            name='icon',
            field=models.CharField(default='A', max_length=16),
            preserve_default=False,
        ),
        migrations.AddField(
            model_name='player',
            name='location_auto',
            field=models.BooleanField(default=False),
            preserve_default=False,
        ),
        migrations.AddField(
            model_name='player',
            name='notify_email',
            field=models.BooleanField(default=False),
            preserve_default=False,
        ),
        migrations.AddField(
            model_name='player',
            name='notify_web',
            field=models.BooleanField(default=False),
            preserve_default=False,
        ),
    ]
