# Generated by Django 5.0.2 on 2024-11-30 14:14

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('player', '0040_strength_unique_relative'),
    ]

    operations = [
        migrations.AddField(
            model_name='bid',
            name='str_conf',
            field=models.CharField(choices=[('a', ''), ('b', 'probably'), ('c', 'maybe')], default='z', max_length=1),
        ),
        migrations.AlterField(
            model_name='bid',
            name='strength',
            field=models.CharField(choices=[('W', 'much-weaker'), ('w', 'weaker'), ('m', 'matched'), ('s', 'stronger'), ('S', 'much-stonger'), ('z', 'unknown')], default='m', max_length=1),
        ),
        migrations.AlterField(
            model_name='strength',
            name='relative',
            field=models.CharField(choices=[('W', 'much-weaker'), ('w', 'weaker'), ('m', 'matched'), ('s', 'stronger'), ('S', 'much-stonger'), ('z', 'unknown')], max_length=1),
        ),
    ]
