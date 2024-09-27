# Generated by Django 5.0.2 on 2024-09-14 20:10

from django.db import migrations, models


class Migration(migrations.Migration):

    initial = True

    dependencies = [
    ]

    operations = [
        migrations.CreateModel(
            name='Service',
            fields=[
                ('name', models.CharField(max_length=32, primary_key=True, serialize=False)),
                ('url', models.URLField(max_length=64)),
                ('key', models.CharField(max_length=64)),
            ],
        ),
    ]