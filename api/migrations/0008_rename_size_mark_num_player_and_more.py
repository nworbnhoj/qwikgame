# Generated by Django 5.0.2 on 2024-12-10 09:18

from django.db import migrations


class Migration(migrations.Migration):

    dependencies = [
        ('api', '0007_mark_content'),
    ]

    operations = [
        migrations.RenameField(
            model_name='mark',
            old_name='size',
            new_name='num_player',
        ),
        migrations.RenameField(
            model_name='mark',
            old_name='content',
            new_name='num_venue',
        ),
    ]
