# Generated by Django 5.0.2 on 2024-09-12 00:30

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('venue', '0008_venue_tz'),
    ]

    operations = [
        migrations.AddField(
            model_name='venue',
            name='address',
            field=models.CharField(blank=True, max_length=256),
        ),
        migrations.AddField(
            model_name='venue',
            name='admin1',
            field=models.CharField(blank=True, max_length=64),
        ),
        migrations.AddField(
            model_name='venue',
            name='country',
            field=models.CharField(blank=True, max_length=2),
        ),
        migrations.AddField(
            model_name='venue',
            name='hours',
            field=models.BinaryField(default=b'\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00'),
        ),
        migrations.AddField(
            model_name='venue',
            name='lat',
            field=models.DecimalField(decimal_places=6, default=-36.449786, max_digits=9),
        ),
        migrations.AddField(
            model_name='venue',
            name='lng',
            field=models.DecimalField(decimal_places=6, default=146.430037, max_digits=9),
        ),
        migrations.AddField(
            model_name='venue',
            name='locality',
            field=models.CharField(blank=True, max_length=64),
        ),
        migrations.AddField(
            model_name='venue',
            name='note',
            field=models.TextField(blank=True, max_length=256),
        ),
        migrations.AddField(
            model_name='venue',
            name='phone',
            field=models.CharField(blank=True, max_length=12),
        ),
        migrations.AddField(
            model_name='venue',
            name='placeid',
            field=models.TextField(blank=True),
        ),
        migrations.AddField(
            model_name='venue',
            name='route',
            field=models.CharField(blank=True, max_length=64),
        ),
        migrations.AddField(
            model_name='venue',
            name='str_num',
            field=models.CharField(blank=True, max_length=8),
        ),
        migrations.AddField(
            model_name='venue',
            name='suburb',
            field=models.CharField(blank=True, max_length=32),
        ),
    ]
