# qwikgame


qwikgame is a system to help people find a rival to play their favorite (real-world) game at a convenient time and place. Players are able to rate each other for both behaviour and ability which enables the system to refine estimates of ability between new rivals. Care has been taken to provide a range privacy options for players. Players are able to add and maintain information about suitable venues to play a range of games from Chess to Squash.

## qwikgame.org

You can use qwikgame for free at [qwikgame.org](https://qwikgame.org)

> When you are **keen** for a game, invitations are sent to all people interested in playing at your chosen venue. Each **invitation** includes your relative **ability** and your **reputation**. One or more people may **accept** your invitation. You should accept ✓ one rival and reject ⨯ the others. You and your chosen rival will now see the Match as **confirmed** and meet on time at the venue.
>
>After the game please revisit qwikgame.org to rate your rivals **ability** as stronger, weaker or well matched. Choose **well matched** when you had an enjoyable challenging match, regardless of who actually 'won'. You should also give your rival the 👍 if they were nice to be around, regardless of who was the stronger player.


## Deployment  

After configuring your favorite web server ([nginx](https://nginx.org) is recommended)
- pull the qwikgame files wit `git pull origin master`
- change the file ownership to the appropriate user `chown -r www-data:www-data *`
- edit `/class/Qwik.php` if required to set `const SUBDOMAIN  = 'www';`


## Development

qwikgame is currently developed with html, css, php, json, javascript and xml data files. [Behat](http://behat.org/) is used for testing.

In qwikgame parlace; **matches** are played between **rival** **players** at **venues**.

### users

Each registered **player** has a single xml file in the `/player` directory.

A player registers by nominating a favorite game and venue, and an email address. The player is effectively registering that they are available to play Squash at the Milawa Courts (for example) and wish to be alerted when some-one else is keen to play. An email registration confirmation contains a link to the players home page and a cookie is set to maintain the user-login beyond a single session. If the user manually logs-out then another email can be requested with a new login link (ie traditional user passwords are replaced by persistent sessions and a simple password-reset email process). 

The internal player ID (pid) is chosen by taking the sha256 hash of the email address. 
This has a number of advantages:
- The player ID will be unique because the email address will be unique
- Qwikgame can accept and use a sha256 hash to store anonymous player data
- A new email address can be linked to existing anonymous player data

A player _must_ nominate:

- at least one favorite game/venue/time combination when they are available to play
- an email address as a unique ID and for notifications

A player may _optionally_ nominate:

- one or more email addresses of known rivals and their relative ability
- an estimate of their own ability for one or more games in one or more geographic regions
- a nickname visible to other players
- a link to a social media page

### venues

Each game **venue** has a single xml file in the `/venue` directory. This directory also contains a subdirectory for each game which contains symlinks back up to the xml files in the `/venue` directory. This provides a straight-forward way to list the venues for a given game.

Players are able to add and maintain information about suitable venues to play each game including Name, Address, GPS location etc.

A unique Venue ID (vid) is constructed from the venue's name and address in the form:
	name|address|locality|state|country
This is used as the XML file name which allows qwikgame to use rapid file-system tools to locate a venue without having to parse an XML file or utilize a database.

### matches

**Match** data is stored in the player xml file in the `/player` directory.

When a player is keen for a match then they can nominate a game, a venue and a range of times over the next 48 hours. This will trigger invitations to be emailed to all other players who have registered a favorite for playing the game at the venue. The invitations contain the estimated relative ability, and reputation of the potential rival. The first player can then choose a rival from those who accept the invitation. After the match, the rivals can rate each other for behaviour and ability. 

Each match has a current status: *keen*, *invitation*, *accepted*, *confirmed*, *feedback*, or *cancelled*.

### html templates

The `/html` directory contains html templates in the form:

    <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <meta charset="UTF-8">
        </head>
        <body>
            <h1>{hello}</h1>
            <p>You have a game of [game] on [day] at [time]</p>
            <h1>{pastRivals}</h1>
            <div>
                <div id='past-rivals' class='base json'>
                    [name] is a [parity] [game] player<br>
                </repeat>
            </div>
        </body>
    </html>

The process to construct a html page from a html template involves 3 steps:
1. The html element with class='base' is replicated for each each data set
2. Each \[variable-key\] is replaced by a value.
3. Each {phrase-key} is replaced by a phrase in the users language.

Note: this implies that elements with class='base' may contain both \[\] and {}; and a value may also contain {}.

4. The class='json' triggers a json update of the innerHTML of the parent <div>


### multilingual

qwikgame supports multiple user languages. Translations are contained in  `/lang/translation.xml` in the form:

    <?xml version="1.0"?>
    <translation>
        <language key="en" dir="ltr">English</language>
        <language key="es" dir="ltr">Espa&#xF1;ol</language>
        <phrase key="hello">
            <en>Hello</en>
            <es>Hola</es>
        </phrase>
    </translation>

A call to `translate.php` reads each _html template_ in `/html`; replaces each {phrase-key}; and writes the now _language specific html template_ to a directory under `/lang` (ie `/lang/en` `/lang/es` `/lang/zh` etc).

A subsequet call to `match.php` (for example) takes the appropriate _language specific html template_, processes the <repeat> elements, populates the [variables] and then translates the {phrases}, before serving the page to the user.

### relative ability

qwikgame makes estimates of the **parity** (relative ability) of potential rivals based on feedback from past rivals (and initially the players own self-assessment). This is NOT based on a ranking or grading of players; it is *relative* assessment. Players can rate each other as well-matched, stronger, weaker, much-stronger or much-weaker. If two players have previously rated each other then their ratings are combined to estimate their relative ability (the players may not be in agreement). Otherwise, qwikgame uses the ratings of a common rival, if direct ratings are not available. More weight is given to more recent ratings; and a record is kept of the reliability of the ratings provided by each player (ie how closely they coincide with rating of their rivals).

### program files

php Class files are located in `/class`

- **Qwik.php**
 - **Defend.php** Sanitize a web request before further php procesing.
 - **Hours.php** The 24 hours in a day are represented as a bitfield.
 - **Html.php** General code to complete a html document based on a html template with \[variables\] and {phrases}.
   - **Email.php**
   - **Page.php** General code to serve a html page including <repeat> elements,  \[variables\] and {phrases}.
    - **IndexPage.php**
    - **InfoPage.php**
    - **LocatePage.php**
    - **PlayerPage.php**
    - **TranslatePage.php**
    - **UploadPage.php**
    - **VenuePage.php**
 - **Logging.php** general code to log messages to a file
 - **Match.php** interface to Match data within a Player's xml data
 - **Node.php** a Node within an Orb contains player ID, parity, reliability, date and a sub-orb.
 - **Orb.php** an array of Nodes Objects used to estimate the parity of two players
 - **Player.php** interface to the xml data of a player.
 - **Ranking.php** represents and processes a ranked list of players.
 - **Translation.php** translate general html templates into language specific html templates.
 - **Venue.php**

Other important files include:
- **qwik.css** cascading style sheet
- **qwik.js** javascript
- **translate.php** translate general html templates into language specific html

General html templates prior to translation. 

- html/**account.html** player account settings
- html/**favourite.html** player favorite game/venues
- html/**friend.html** player friends
- html/**index.html** homepage
- html/**info.html** user help and info
- html/**locate.html** locate a game venue
- html/**match.html** player matches
- html/**upload.html** upload player rankings
- html/**venue.html** venue details
- html/**venues.html**

language specific translation files

- lang/**en/*.php** english translation
- lang/**es/*.php** spanish translation
- lang/**zh/*.php** chinese translation

json

- json/**timezone.php** populate a select list with timezone options.
- json/**venue-map-data** generate pins for a google map.

### web push


[Web Push Notifications](https://github.com/web-push-libs/web-push-php) by [Minishlink](https://github.com/Minishlink)

