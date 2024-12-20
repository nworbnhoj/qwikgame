# Generated by Django 5.0.2 on 2024-09-24 04:56

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('api', '0003_delete_service'),
    ]

    operations = [
        migrations.AddField(
            model_name='region',
            name='lat',
            field=models.DecimalField(decimal_places=6, default=0, max_digits=9),
        ),
        migrations.AddField(
            model_name='region',
            name='lng',
            field=models.DecimalField(decimal_places=6, default=0, max_digits=9),
        ),
    ]
