# Generated by Django 5.0.2 on 2024-10-31 04:47

import django.db.models.deletion
import django.utils.timezone
from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('player', '0035_player_conduct_delete_conduct'),
    ]

    operations = [
        migrations.RemoveField(
            model_name='strength',
            name='opinion',
        ),
        migrations.AddField(
            model_name='strength',
            name='date',
            field=models.DateTimeField(default=django.utils.timezone.now),
            preserve_default=False,
        ),
        migrations.AddField(
            model_name='strength',
            name='player',
            field=models.ForeignKey(default='59f843d7df3f5afd981439181c647b20', on_delete=django.db.models.deletion.CASCADE, related_name='basis', to='player.player'),
            preserve_default=False,
        ),
        migrations.AddField(
            model_name='strength',
            name='rival',
            field=models.ForeignKey(default='6e84b88cdec077a5661134f46d571ccf', on_delete=django.db.models.deletion.CASCADE, related_name='relative', to='player.player'),
            preserve_default=False,
        ),
        migrations.AlterField(
            model_name='player',
            name='conduct',
            field=models.BinaryField(default=b'\x00\xff\xff'),
        ),
        migrations.DeleteModel(
            name='Opinion',
        ),
    ]