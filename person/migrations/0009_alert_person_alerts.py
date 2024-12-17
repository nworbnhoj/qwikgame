# Generated by Django 5.0.2 on 2024-12-05 07:07

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('person', '0008_person_language'),
    ]

    operations = [
        migrations.CreateModel(
            name='Alert',
            fields=[
                ('id', models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name='ID')),
                ('expires', models.DateField()),
                ('priority', models.CharField(max_length=1)),
                ('text', models.CharField(max_length=256)),
            ],
        ),
        migrations.AddField(
            model_name='person',
            name='alerts',
            field=models.JSONField(default=list),
        ),
    ]
