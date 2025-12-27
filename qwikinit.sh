#! /usr/bin/bash

set -e

url=$(git config --get remote.origin.url)
repo=$(basename "$url" .git)
if [[ $repo -ne 'qwikgame' ]];
then
    echo "script should be run  at the root of the qwikgame git repo"
    exit 0
fi

# echo " ====== SETUP PYTHON ======"
# python3 -m venv venv
# source venv/bin/activate
# python3 -m pip install --upgrade pip setuptools wheel
# python3 -m pip install django django-crontab django-webpush dotenv gunicorn pipreqs psycopg2-binary python-dateutil requests

echo " ====== SETUP POSTGRESQL ======"
sudo systemctl start postgresql
sudo systemctl status postgresql
cp qwikinit.sql /tmp
sudo -u postgres psql -f /tmp/qwikinit.sql
sudo systemctl restart postgresql

echo " ====== SETUP POSTGRESQL LOCAL USER ======"
if [ $USER -ne 'alpha' ] && [ $USER -ne 'beta' ] && [ $USER -ne 'qwikgame' ];
then
    local_password='password'
    sudo -u postgres psql -c "CREATE USER $USER WITH PASSWORD $local_password;"
    sudo -u postgres psql -c "GRANT postgres TO $USER;"
    sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE alpha TO $USER;"
    sudo systemctl restart postgresql
    sed -i 's/DATABASE_USER="alpha"/DATABASE_USER="$USER"/g' .env
    sed -i 's/DATABASE_PASSWORD="alpha"/DATABASE_PASSWORD=$local_password/g' .env
fi

echo "====== SETUP DJANGO ======"
python3 manage.py createsuperuser
python3 manage.py migrate 

echo " ====== RESTORE DATABASE ======"
read -p "Enter database archive filename to restore: " filename
if [ -f $file_name ];
then
    psql -d alpha -f $filename
fi

# python3 manage.py runserver
# http://localhost:8000/

echo "====== INSTALL CONFIG FILES ======"
sudo cp etc/gunicorn.py /etc/gunicorn.py
sudo cp etc/nginx/sites-available/qwikgame /etc/nginx/sites-available/qwikgame
sudo cp etc/systemd/system/gunicorn.service /etc/systemd/system/gunicorn.service