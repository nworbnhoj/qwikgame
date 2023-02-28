#!/usr/bin/env bash

echo -n "Setting file permissions"

cd "$(git rev-parse --show-toplevel)" ; echo -n "."

chown -R www-admin:www *              ; echo -n "."
chmod -R u+rwX *                      ; echo -n "."
chmod -R go-rwx *                     ; echo -n "."

chmod -R g+rX  class                  ; echo -n "."
chmod -R g+rx  cron                   ; echo -n "."
chmod -R g+rwX delayed                ; echo -n "."
chmod -R g+rX  html                   ; echo -n "."
chmod -R g+rwX lang                   ; echo -n "."
chmod -R g+rwX mark                   ; echo -n "."
chmod -R g+rwX uploads                ; echo -n "."
chmod -R g+rwX user                   ; echo -n "."
chmod -R g+rX  vendor                 ; echo -n "."
chmod -R g+rwX venue                  ; echo -n "."
chmod -R g+rX  www                    ; echo -n "."

chmod    g+r   path.php               ; echo -n "."
chmod    g+r   services.xml           ; echo -n "."
chmod    g+r   up.php                 ; echo -n "."

touch /var/log/qwikgame.org/beta.log
chown www-admin:www /var/log/qwikgame.org/beta.log
chmod 660 /var/log/qwikgame.org/beta.log

echo "."