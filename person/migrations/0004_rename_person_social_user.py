# Generated by Django 5.0.2 on 2024-03-18 00:47

from django.db import migrations


class Migration(migrations.Migration):

    dependencies = [
        ('person', '0003_alter_social_person'),
    ]

    operations = [
        migrations.RenameField(
            model_name='social',
            old_name='person',
            new_name='user',
        ),
    ]
