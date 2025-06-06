# Generated by Django 5.0.2 on 2024-10-06 21:39

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('venue', '0009_venue_address_venue_admin1_venue_country_venue_hours_and_more'),
    ]

    operations = [
        migrations.CreateModel(
            name='Region',
            fields=[
                ('id', models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('admin1', models.CharField(blank=True, max_length=64, null=True)),
                ('country', models.CharField(max_length=2)),
                ('east', models.DecimalField(decimal_places=6, default=180, max_digits=9)),
                ('name', models.CharField(blank=True, max_length=128)),
                ('lat', models.DecimalField(decimal_places=6, default=0, max_digits=9)),
                ('lng', models.DecimalField(decimal_places=6, default=0, max_digits=9)),
                ('north', models.DecimalField(decimal_places=6, default=90, max_digits=9)),
                ('placeid', models.TextField()),
                ('south', models.DecimalField(decimal_places=6, default=-90, max_digits=9)),
                ('west', models.DecimalField(decimal_places=6, default=-180, max_digits=9)),
                ('locality', models.CharField(blank=True, max_length=64, null=True)),
            ],
        ),
    ]
