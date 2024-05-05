# Generated by Django 5.0.2 on 2024-04-19 19:52

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('game', '0003_alter_game_icon'),
        ('player', '0020_alter_friend_rival'),
        ('venue', '0007_alter_venue_managers'),
    ]

    operations = [
        migrations.RemoveField(
            model_name='invite',
            name='hour',
        ),
        migrations.AddField(
            model_name='invite',
            name='hours',
            field=models.BinaryField(default=b'\x00\x00\x00'),
            preserve_default=False,
        ),
        migrations.AlterField(
            model_name='appeal',
            name='date',
            field=models.DateField(),
        ),
        migrations.AlterField(
            model_name='appeal',
            name='hours',
            field=models.BinaryField(default=b'\x00\x00\x00'),
        ),
        migrations.AddConstraint(
            model_name='appeal',
            constraint=models.UniqueConstraint(fields=('date', 'game', 'player', 'venue'), name='unique_appeal'),
        ),
    ]