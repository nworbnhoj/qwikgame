# Generated by Django 5.1.7 on 2025-04-10 04:32

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('person', '0018_alter_alert_type'),
    ]

    operations = [
        migrations.AddField(
            model_name='alert',
            name='mode',
            field=models.CharField(choices=[('A', 'App'), ('E', 'Email'), ('S', 'SMS'), ('W', 'web')], default='E', max_length=1),
            preserve_default=False,
        ),
    ]
