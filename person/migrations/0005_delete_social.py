# Generated by Django 5.0.2 on 2024-03-18 05:05

from django.db import migrations


class Migration(migrations.Migration):

    dependencies = [
        ('person', '0004_rename_person_social_user'),
    ]

    operations = [
        migrations.DeleteModel(
            name='Social',
        ),
    ]