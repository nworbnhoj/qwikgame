#! /usr/bin/bash

set -e

repo=basename $(git config --get remote.origin.url) .git
if [[ $repo -ne 'qwikgame' ]];
then
    echo "script should be run  at the root of the qwikgame git repo"
    exit 0
fi

echo " ====== SETUP PYTHON ======"
python3 -m venv venv
source venv/bin/activate
python3 -m pip install --upgrade pip setuptools wheel
python3 -m pip install django django-crontab django-webpush dotenv gunicorn pipreqs psycopg2-binary python-dateutil requests

echo " ====== SETUP POSTGRESQL ======"
systemctl start postgresql
systemctl status postgresql
sudo -u postgres psql -h localhost  -f qwikinit.sql
systemctl restart postgresql

echo "====== SETUP DJANGO ======"
python3 manage.py createsuperuser
python3 manage.py migrate 


# python3 manage.py runserver
# http://localhost:8000/