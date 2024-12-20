# Generated by Django 5.0.2 on 2024-03-12 22:39

import django.db.models.deletion
from django.conf import settings
from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('venue', '0002_venue_games'),
        migrations.swappable_dependency(settings.AUTH_USER_MODEL),
    ]

    operations = [
        migrations.CreateModel(
            name='Manager',
            fields=[
                ('id', models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('icon', models.CharField(max_length=16)),
                ('location_auto', models.BooleanField()),
                ('notify_email', models.BooleanField()),
                ('notify_web', models.BooleanField()),
                ('user', models.OneToOneField(on_delete=django.db.models.deletion.CASCADE, to=settings.AUTH_USER_MODEL)),
            ],
        ),
        migrations.AddField(
            model_name='venue',
            name='managers',
            field=models.ManyToManyField(to='venue.manager'),
        ),
    ]
