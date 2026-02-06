# qwikgame

qwikgame is a system to help people find a rival to play their favorite (real-world) game at a convenient time and place. Players are able to rate each others ability to enable qwikgame to estimate the relative strength between new rivals. Care has been taken to provide a range privacy options for players. Players are able to add and maintain information about suitable venues to play a range of games from Chess to Squash.


## qwikgame.org

You can use qwikgame for free at [qwikgame.org](https://qwikgame.org)

> When you are **keen** for a game, you can invite other **players** to a **match** at your preferred **venue** and a range of times. Alternatively you can browse invitations from other **players** and accept one of their available times. Each **invitation** includes your qwikgame **reputation** and estimates of the relative strength of the other **players**. Once a **match** is confirmed, you are able to chat with your rival before meeting on time at the **venue**.
>
>After a Match, you should rate your rivals **ability** as stronger, weaker or well matched. **well matched** indicates an enjoyable challenging match, regardless of who actually 'won'. You may also bolster the reputation of your rival by recognising good sportsmanship, regardless of who was the stronger player.


## Deployment   

Typical setup might involve:
- linux on a virtual server
- nginx and lets-encrypt webserver
- psql database
- python3, django and gunicorn

### Environment Variables

The following envonment variables are required:
- EMAIL_BACKEND          # default django.core.mail.backends.console.EmailBackend
- EMAIL_HOST             # e.g.    mail.com
- EMAIL_PORT             # default 587
- EMAIL_USE_TLS          # default True
- EMAIL_ACCOUNT_USER     # e.g.    accounts@qwikgame.org
- EMAIL_ACCOUNT_PASSWORD
- EMAIL_ACCOUNT_NAME     # e.g.    QWIK ACCOUNTS
- EMAIL_ALERT_USER       # e.g.    alerts@qwikgame.org
- EMAIL_ALERT_PASSWORD
- EMAIL_ALERT_NAME       # e.g.    QWIK ALERTS


## Development

qwikgame is written in html, css, python3, json, javascript, django and psql.

In qwikgame parlace; **Matches** are played between **Rival** **Players** at **Venues**.

### relative ability

qwikgame makes estimates of the **parity** (relative ability) of potential rivals based on feedback from past rivals (and initially the players own self-assessment). This is NOT based on a ranking or grading of players; it is *relative* assessment. Players can rate each other as well-matched, stronger, weaker, much-stronger or much-weaker. If two players have previously rated each other then their ratings are combined to estimate their relative ability (the players may not be in agreement). Otherwise, qwikgame uses the ratings of a common rival, if direct ratings are not available. More weight is given to more recent ratings; and a record is kept of the reliability of the ratings provided by each player (ie how closely they coincide with rating of their rivals).