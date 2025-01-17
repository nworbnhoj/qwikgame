# qwikgame

qwikgame is a system to help people find a rival to play their favorite (real-world) game at a convenient time and place. Players are able to rate each others ability to enable qwikgame to estimate the relative strength between new rivals. Care has been taken to provide a range privacy options for players. Players are able to add and maintain information about suitable venues to play a range of games from Chess to Squash.


## qwikgame.org

You can use qwikgame for free at [qwikgame.org](https://qwikgame.org)

> When you are **keen** for a game, you can invite other **players** to a **match** at your preferred **venue** and a range of times. Alternatively you can browse invitations from other **players** and accept one of their available times. Each **invitation** includes your qwikgame **reputation** and estimates of the relative strength of the other **players**. Once a **match** is confirmed, you are able to chat with your rival before meeting on time at the **venue**.
>
>After a Match, you should rate your rivals **ability** as stronger, weaker or well matched. **well matched** indicates an enjoyable challenging match, regardless of who actually 'won'. YOu may also bolster the reputation of your rival by recognising good sportsmanship, regardless of who was the stronger player.


## Deployment  

Typical setup might involve:
- linux on a virtual server
- nginx and lets-encrypt webserver
- psql database
- python3, django and gunicorn

## Development

qwikgame is written in html, css, python3, json, javascript, django and psql.

In qwikgame parlace; **Matches** are played between **Rival** **Players** at **Venues**.

### users

qwikgame user roles are represented by a number of class structures:
- **User**: represents a validated email address - and controls access to system admin functions
- **Person**: represents the person who validated the **User** email address - and records preferences such as screen name/icon, language, and various privacy aspects.
- **Player**: represents a **User** who plays Games with other Players - and is the basis for recording Friends, Conduct and relative Game Strength
- **Manager**: represents a **User** with responsibility for bookings at a Venue.

Player registration involves the validation of an email address and the creation of **User**, **Person**, and **Player** objects in the system. Registration cascades to login - affected as a session on the device with a lengthy timeout. The session can be manually terminated (ie logout) as required.

Manager registration similarly involves the validation of an email address and the creation of **User**, **Person**, and **Manager** objects in the system. A **User** can be associated with both **Player** and **Manager** objects.

User login involves the re-validation of an email address and re-establishment of a session on a device. Login may be required when a session is logged out, timed-out, or a session is required on a different device.

A **Person** can choose to block any other **Person** on the system. This causes these two **Persons** to be _mutually_ invisible to each other.

A **Player** can add a Friend with an email address. If the email address has NOT yet been registered with qwikgame, then a new unregistered **Player** object is created (without the associated **User** or **Person** objects). Unregistered **Player** objects play an important role in holding the relative Game Strengths against other registered **Players**. Note that multiple registered **Players** may independently add a Friend with the same email address. These Friend objects will all be associated with a single **Player** object.

When a **Player** invites an unregistered **Player** Friend to a Match, then an email is sent to the Friend. All three links in the invitation email will validate the Friend's email address, and create the associated **User** and **Person** objects as above. In addition:
  - the _accept_ link will redirect the new **Player** directly to the Bid form so that they may accept the invitation.
  - the _block_ link will establish a _block_ between the two **Person** objects. The session will last only long enough to complete the transaction.
  - the _block_all_ link will establish a _block_ between the new **Person** object and a special **Person** "wildcard" object. The session will last only long enough to complete the transaction.

When a **User** deletes their qwikgame account then the **Player** effectively returns to unregistered status (almost) :
  - the **User** object will be deleted, with the associated email address
  - the **Person** object will be deleted with all associated preferences including _blocked_ **Persons**
  - the **Player** object will be retained, to maintain the important relative Game Strength relationships. All associated Bid, Appeal, Match and Friend objects will be deleted.
  - The **Player** conduct record will be retained along with a hash of the email address - so that on re-registration the conduct record will persist.
  - Any Friend objects in other **Player** accounts will persist, and as such, it is possible for the departed email address to receive invitation emails. Unwanted invitation emails can be blocked as for any unregistered email (as above)

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
- json/**venue-marks** generate markers for a google map.

### web push


[Web Push Notifications](https://github.com/web-push-libs/web-push-php) by [Minishlink](https://github.com/Minishlink)

