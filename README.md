# qwikgame


qwikgame is a system to help people find a rival to play their favourite (real-world) game at a convenient time and place. Players are able to rate each other for both behaviour and ability which enables the system to refine estimates of ability between new rivals. Care has been taken to provide a range privacy options for players. Players are able to add and maintain information about suitable venues to play a range of games from Chess to Squash.

## qwikgame.org

You can use qwikgame for free at [qwikgame.org](https://qwikgame.org)

> When you are **keen** for a game, invitations are sent to all people interested in playing at the venue. Each **invitation** includes your relative **ability** and your **reputation**. One or more people may **accept** your invitation. You should accept ✓ one rival and reject ⨯ the others. You and your chosen rival will now see the Match as **confirmed** and meet on time at the venue.
>
>After the game please revisit qwikgame.org to rate your rivals **ability** as stronger, weaker or well matched. Choose **well matched** when you had an enjoyable challenging match, regardless of who actually 'won'. You should also give your rival the 👍 if they were nice to be around, regardless of who was the stronger player.

***
***

## Development

qwikgame is currently developed with html, css, php, json, javascript, jquery and xml data files.

In qwikgame parlace; **matches** are played between **rival** **players** at **venues**.

### users

Each registered **player** has a single xml file in the `/player` directory.

A player registers by nominating a game, a venue and an email address. The player is effectively registering that they are available to play Squash at the Milawa Courts (for example) and wish to be alerted when some-one else is keen to play. An email registration confirmation contains a link to the players home page and a cookie is set to maintain the user-login beyond a single session. If the user manually logs-out then another email can be requested with a new login link (ie the user passwords are replaced by persistent sessions and a simple password-reset email process). 

The internal player ID is chosen by taking the sha256 hash of the email address. This has a number of advantages:
- The player ID will be unique because the email address will be unique
- Qwikgame can accept and use a sha256 hash to store anonymous player data
- A new email address can be linked to existing anonymous player data

A player must nominate:

- at least one favourite game/venue/time combination when they are available to play
- an email address as a unique ID and for notifications

A player may optionally nominate:

- one or more email addresses of known rivals and their relative ability
- an estimate of their own ability for one or more games in one or more geographic regions
- a nickname visible to other players
- a link to a social media page

### venues

Each game **venue** has a single xml file in the `/venue` directory.

Players are able to add and maintain information about suitable venues to play each game including Name, Address, GPS location etc.

### matches

**Match** data is stored in the player xml file in the `/player` directory.

When a player is keen for a match then they can nominate a game, a venue and a range of times over the next 48 hours. This will trigger invitations to be emailed to all other players who have registered their interest in playing the game at the venue. The invitations contain the estimated relative ability, and reputation of the potential rival. The first player can then choose a rival from those who accept the invitation. After the match, the rivals can rate each other for behaviour and ability. 

Each match has a current status: *keen*, *invitation*, *accepted*, *confirmed*, *feedback*, or *cancelled*.

### multilingual

qwikgame supports multiple user languages. Variables in html templates are replaced (php) to generate a html document in each of the available languages. Static html is generated periodically as required, while the remaining dynamic elements are inserted on-the-fly. Each language translation exists as a file defining each of the variables to be inserted into the html template (eg $zh['tagline'] = '找人玩你最喜欢的游戏，在一个适合你的时间和地点';). There are currently translations for en, es & zh (additional translations most welcome).

### relative ability

qwikgame makes estimates of the **relative ability** of potential rivals based on feedback from past rivals (and initially the players own self-assessment). This is NOT based on a ranking or grading of players; it is *relative* assessment. Players can rate each other as well-matched, stronger, weaker, much-stronger or much-weaker. If two players have previously rated each other then their ratings are combined to estimate their relative ability (the players may not be in agreement). Otherwise, qwikgame uses the ratings of a common rival, if direct ratings are not available. More weight is given to more recent ratings; and a record is kept of the reliability of the ratings provided by each player (ie how closely they coincide with rating of their rivals).

### program files

- **qwik.php** main library of php routines. Important functions include:
  - logMsg($msg)
  - SECURITYsanitizeHTML($data)
  - post($url, $data)
  - login($req)
  - language($req, $player)
  - translate($html, $lang, $fb='en')
  - populate($html, $variables)
  - replicate($html, $player, $req)
  - validate($req)
  - parity($player, $rival, $game)
  - uploadRanking($player, $game, $title)
  - writeXML($path, $file, $xml)
  - readXML($path, $file)
  - fileList($dir)
  - qwikEmail($to, $subject, $msg, $id, $token)
- **qwik.css** cascading style sheet
- **qwik.js** javascript
- **translate.php** translate general html templates into language specific html

General html templates prior to translation. 

- **error.html** error handling
- **index.html** homepage
- **info.html** user help and info
- **locate.html** locate a game venue
- **player.html** player specific homepage
- **upload.html** upload player rankings
- **venue.html** venue details
- **venues.html**

php to generate dynamic html from language specific html templates. These php files have have a common php structure: validate and sanatize *post&get* data; identify logged-in player (if any); obtain player language; process *post&get* data to set variables; obtain the html template; replicate repeating elements (ie lists, rows, options etc); and translate dynamic variables.

- **error.php** 
- **index.php** 
- **info.php** 
- **locate.php** 
- **player.php** 
- **upload.php** 
- **venue.php** 
- **venues.php** 

language translation files

- **/en/lang.php** english translation
- **/es/lang.php** spanish translation
- **/zh/lang.php** chinese translation

json

- **hours.php** generate a table with specific cells hilighted.
- **regions.php** populate a select list with geographic region options.
- **timezone.php** populate a select list with timezone options.
- **venue-map-data** generate pins for a google map.






