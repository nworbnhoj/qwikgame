# Generated by Django 5.0.2 on 2024-04-28 18:16

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('player', '0021_remove_invite_hour_invite_hours_alter_appeal_date_and_more'),
    ]

    operations = [
        migrations.AlterField(
            model_name='invite',
            name='hours',
            field=models.BinaryField(default=None),
        ),
    ]