# Generated by Django 5.1.4 on 2025-02-17 04:58

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('feedback', '0002_alter_feedback_date'),
    ]

    operations = [
        migrations.AddField(
            model_name='feedback',
            name='path',
            field=models.CharField(default='', editable=False, max_length=128),
            preserve_default=False,
        ),
    ]
