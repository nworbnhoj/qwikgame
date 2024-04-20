# Generated by Django 5.0.2 on 2024-04-16 07:10

import django.db.models.deletion
from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('player', '0019_alter_available_unique_together_and_more'),
    ]

    operations = [
        migrations.AlterField(
            model_name='friend',
            name='rival',
            field=models.ForeignKey(on_delete=django.db.models.deletion.CASCADE, related_name='usher', to='player.player'),
        ),
    ]
