# Generated by Django 5.0.2 on 2024-04-28 18:56

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('player', '0022_alter_invite_hours'),
    ]

    operations = [
        migrations.AlterField(
            model_name='invite',
            name='hours',
            field=models.BinaryField(default=None, null=True),
        ),
    ]
