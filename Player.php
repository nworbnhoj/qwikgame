<?php


include 'Orb.php';
include 'Match.php';


class Player {

    const PATH_PLAYER   = 'player';
    const PATH_UPLOAD = 'uploads';

    const SECOND = 1;
    const MINUTE = 60;
    const HOUR   = 3600;
    const DAY    = 86400;
    const WEEK   = 604800;
    const MONTH  = 2678400;
    const YEAR   = 31536000;

    private $id;
    private $xml;
    private $log;

    public function __construct($pid, $log, $forge=FALSE){
        $this->id = $pid;
        $this->log = $log;
        $path = self::PATH_PLAYER;
        if (!file_exists("$path/$pid.xml") && $forge) {
            $this->xml = $this->newXML($pid);
            $this->save();
	        $this->logMsg("login: new player " . snip($pid));
        }
        $this->xml = $this->readXML($pid);
    }


    private function newXML(){
        $id = $this->id;
        $now = new DateTime('now');
        $debut = $now->format('d-m-Y');
        $record  = "<player id='$id' debut='$debut' lang='en'>";
        $record .= "<rep pos='0' neg='0'/>";
        $record .= "<rely val='2.0'/>";
        $record .= "</player>";
        return new SimpleXMLElement($record);
    }


    public function save(){
        $cwd = getcwd();
        if(chdir(self::PATH_PLAYER)){
            $pid = $this->xml['id'];
            $this->xml->saveXML("$pid.xml");
            if(!chdir($cwd)){
                $this->logMsg("failed to change working directory to $cwd");
                return false;
            }
        } else {
            $this->logMsg("failed to change working directory to ".self::PATH_PLAYER);
            return false;
        }
        return true;
    }


    public function readXML($id){
        $id = $this->id;
        $path = self::PATH_PLAYER;
        $filename = "$id.xml";
        if (!file_exists("$path/$filename")) {
            $this->logMsg("unable to read player XML " . snip($id));
            return null;
        }

        $cwd = getcwd();
        if(chdir($path)){
            $xml = simpleXML_load_file($filename);
            if(!chdir($cwd)){
                $this->logMsg("failed to change working directory to $cwd");
            }
            return $xml;
        } else {
            $this->logMsg("failed to change working directory to $path");
        }
    }


    private function logMsg($msg){
        $this->log->lwrite($msg);
        $this->log->lclose();
    }


    public function exists(){
        return !is_null($this->xml);
    }


    public function id(){
        return $this->id;
    }


    public function debut(){
        return $this->debut;
    }


    public function lang($lang=NULL){
        if (!is_null($lang)){
            $this->xml['lang'] = $lang;
        }
        return (string) $this->xml['lang'];
    }


    public function nick($nick=NULL){
        if (!is_null($nick)){
            if (isset($this->xml['nick'])){
                $this->xml['nick'] = $nick;
            } else {
                $this->xml->addAttribute('nick', $nick);
            }
        }
        return (string) $this->xml['nick'];
    }


    public function rely($disparity=NULL){            // note disparity range [0,4]
        $rely = $this->xml['rely']['val'];
        if (isset($disparity)){
            $this->xml->rely['val'] = $this->expMovingAvg($rely, 4-$disparity, 3);
        }
        return (float) $this->xml['rely']['val'];
    }


    public function rep(){
        return $this->xml['rep'][0];
    }


    public function url($url=NULL){
        if (!is_null($url)){
            if (isset($this->xml['url'])){
                $this->xml['url'] = $url;
            } else {
                $this->xml->addAttribute('url', $url);
            }
        }
        return (string) $this->xml['url'];
    }



    public function email($newEmail=NULL){
        if (!is_null($newEmail)){
            if(empty($this->xml['email'])){
                $this->xml->addChild('email', $newEmail);
            } else {
                $oldEmail = $this->xml['email'][0];
                if ($oldEmail != $newEmail) {
                    $newID = anonID($newEmail);
                    if (false){ // if newID already exists then log and abort
                        logMsg("abort change email from $oldEmail to $newEmail.");
                    } else {
                        changeEmail($newEmail);
                    }
                }
            }
        }
        return (string) $this->xml['email'];
    }


    private function changeEmail($newEmail){
        $preID = $this->id();
        $newID = anonID($newEmail);
        removeElement($this->xml['email']);
        $this->xml->addChild('email', $newEmail);
        $this->id = $newID;
        $this->xml['id'] = $newID;

        // replace old player file with a symlink to the new file
        $path = self::PATH_PLAYER;
        deleteFile("$path/$preID.xml");
        symlink("$path/$newID.xml", "$path/$preID.xml");
    }



    public function token($term){
        $token = newToken(10);
        $nekot = $this->xml->addChild('nekot', nekot($token));
        $nekot->addAttribute('exp', time() + $term);
        return $token;
    }


    public function available(){
        return $this->xml->xpath("available");
    }


    public function matchStatus($status){
        return $this->xml->xpath("match[@status='$status']");
    }


    public function matchID($id){
        return $this->xml->xpath("match[@id='$id']")[0];
    }


    public function outcome($id, $rely=NULL){
        $outcomes = $this->xml->xpath("outcome[@id='$id']");
        if (empty($outcomes)){
            return null;
        }

        $outcome = $outcomes[0];

        if (is_null($rely)){
            return $outcome;
        }

        $outcome['rely'] = $rely;
        return $outcome;
    }


    public function rankAdd($id, $game, $rid, $parity){
        $rank = $this->xml->addChild('rank', '');
        $rank->addAttribute('id', $id);
        $rank->addAttribute('game', $game);
        $rank->addAttribute('rival', $rid);
        $rank->addAttribute('parity', $parity);
        $rank->addAttribute('rely', '3.0');
        $datetime = date_create();
        $date = $datetime->format('d-m-Y');
        $rank->addAttribute('date', $date);
    }

        
    public function reckon($id){ 
        return $this->xml->xpath("reckon[@$id]");
    }


    public function matchQuery($query){
        return $this->xml->xpath("$query");
    }


    public function log(){
        return $this->log;
    }


    public function matchCancel($id){
        $match = $this->matchID($id);
        $match->cancel();
    }


    public function isValidToken($token){
        $nekot = nekot($token);
        return count($this->xml->xpath("/player/nekot[text()='$nekot']"))>0;
    }


    //scans and processes past matches
    public function concludeMatches(){
    //echo "<br>CONCLUDEMATCHES<br>";
        $matchXMLs = $this->xml->xpath('match');
        foreach($matchXMLs as $xml){
            $match = new Match($this, $xml);
            $match->conclude();
        }
    }



    public function venueRename($vid, $newID){
        $matches = $player->xpath("available[venue='$vid'] | match[venue='$vid']");
        foreach($matches as $match){
            $match->venue = $newID;
        }
    }


    // https://stackoverflow.com/questions/262351/remove-a-child-with-a-specific-attribute-in-simplexml-for-php/16062633#16062633
    public function delete($id){
        $rubbish = $this->xml->xpath("//*[@id='$id']");
        foreach($rubbish as $junk){
            removeElement($junk);
        }
    }

    public function quit(){
        $matches = $this->xml->xpath("match[@status!='history']");
        foreach($matches as $match){
            $match->cancel();
        }
        $records = $this->xml->xpath("available | reckon");
        foreach($records as $record){
            removeElement($record);
        }
        $emails = $this->xml->xpath("email");
        foreach($emails as $email){
            removeElement($email);
        }

        $this->nick(null);
        $this->url(null);
    }



////////// HELPER /////////////////////////////////////


    private function removeElement($xml){
        $dom=dom_import_simpleXML($xml);
        $dom->parentNode->removeChild($dom);
    }

    private function removeAtt($xml, $att){
        $dom=dom_import_simpleXML($xml);
        $dom->removeAttribute($att);
    }


    // Updates an EXPonential moving AVeraGe with a new data POINT.
    private function expMovingAvg($avg, $point, $exp){
        $avg = floatval($avg);
        $point = floatval($point);
        $exp = intval($exp);
        return ($avg * ($exp-1) + $point) / $exp;
    }



////////// RECKON ///////////////////////////////////////

    public function familiar($game, $rivalEmail, $parity, $log){
        $rid = anonID($rivalEmail);

        $reckon = $this->xml->addChild('reckon', '');
        $reckon->addAttribute('rival', $rid);
        $reckon->addAttribute('email', $rivalEmail);
        $reckon->addAttribute('parity', $parity);
        $reckon->addAttribute('game', $game);
        $date = date_create();
        $reckon->addAttribute('date', $date->format("d-m-Y"));
        $reckon->addAttribute('id', newID());
        $reckon->addAttribute('rely', $this->rely()); //default value

        $rival =  new Player($rid, $log);
        $rival->save();
    }


    public function region($game, $ability, $region){
        $reckon = $this->xml->addChild('reckon', '');
        $reckon->addAttribute('ability', $ability);
        $reckon->addAttribute('region', $region);
        $reckon->addAttribute('game', $game);
        $date = date_create();
        $reckon->addAttribute('date', $date->format("d-m-Y"));
        $reckon->addAttribute('id', newID());
        $reckon->addAttribute('rely', $this->rely()); //default value
    }



////////// AVAILABLE //////////////////////////////////////

    public function availableAdd($game, $vid, $parity, $tz, $all7days, $req){
        $newID = newID();
        $element = $this->xml->addChild('available', '');
        $element->addAttribute('id', $newID);
        $element->addAttribute('game', $game);
        $element->addAttribute('parity', $parity);
        $v = $element->addChild('venue', $vid);
        $v->addAttribute('tz', $tz);
        $days = array('Sun', 'Mon', 'Tue','Wed', 'Thu', 'Fri', 'Sat');
        foreach($days as $day){
            $requestHrs = $all7days;
            if (!$requestHrs && isset($req[$day])) {
                $requestHrs = $req[$day];
            }
            if ($requestHrs) {
                $hrs = $element->addChild('hrs', $requestHrs);
                $hrs->addAttribute('day', $day);
            }
        }
        return $newID;
    }


    /**
     * Computes the hours that a $rival is available to have a $match with $player
     *
     * @param xml $rival The $rival who may be available
     * @param xml $player The keen $player who has initiated the $match
     * @param xml @match The $match proposed by the $player to the $rival
     *
     * @return bitfield representing the hours at the $rival is available
     */
    public function availableHours($rival, $match){
    //echo "<br>AVAILABLEHOURS<br>";
        $availableHours = 0;
        $vid = $match->vid();
        $game = $match->game();
        $day = $match->dateTime()->format('D');
        $available = $this->xml->xpath("available[venue='$vid' and @game='$game']");
        foreach ($available as $avail){
            $hours = $avail->xpath("hrs[@day='$day']");
            foreach ($hours as $hrs){
                $availableHours = $availableHours | $hrs;
            }
        }
        return $availableHours;
    }


    // check rival keeness in the given hours
    public function keenHours($rival, $match){
    //echo "<br>KEENHOURS<br>";
        $keenHours = 0;
        $venue = $match->venue();
        $game = $match->game();
        $day = $match->dateTime()->format('D');
        $keens = $this->xml->xpath("match[status='keen' and venue='$venue' and game='$game']");
        foreach ($keens as $keen){
            $keenHours = $keenHours | $keen['hrs'];
        }
        return $keenHours;
    }






////////// MATCH //////////////////////////////////////////

    private function newMatch(){
        $match = new Match(
            $this, 
            $this->xml->addChild('match', '')
        );
        return $match;
    }


    public function matchKeen($game, $venue, $date, $hours) {
        $match = $this->newMatch();
        $match->init('keen', $game, $venue, $date, $hours);
        $venue->addPlayer($this->id());
        return $match;
    }


    public function matchInvite($rivalMatch, $hours=NULL){
        $rid = $rivalMatch->pid();
        $rival = new Player($rid, $this->log);
        $game = $rivalMatch->game();
        $match = $this->newMatch();
        $match->copy($rivalMatch);
        $match->status('invitation');
        if (!is_null($hours)){
            $match->hrs($hours);
        }
        $match->addRival(
            $rid,
            $this->parity($rival, $game),
            $rival->repWord(),
            $rival->nick()
        );
        return $match;
    }


    function matchDecline($mid){
        $match = $this->matchID($mid);
        $match->decline();
        $player->save();
    }


    function matchMsg($mid, $msg){
        $match = $this->matchID($mid);
        if (isset($match)){
            $rival = $match->rival();
            if($rival->exists()){
                $rival->emailMsg($msg, $match);
            }
        }
    }





    function loginURL($shelfLife){
        $pid = $this->id();
        $token = $rival->token($shelfLife);
        $data = array('qwik'=>'login', 'pid'=>$pid, 'token'=>$token);
        return QWIK_URL."/player.php?" . http_build_query($data);
    }


    function emailInvite($mid){
        $match = $this->matchID($mid);
        $date = $match->dateTime();
        $day = $match->day();
        $game = $match->game();
        $venueName = $match->venueName();
        $email = $this->email();
        $url = loginURL(2*self::DAY);

        $subject = "Invitation: $game at $venueName";

        $msg  = "<p>\n";
        $msg .= "\tYou have been invited to play <b>$game $day</b> at $venueName.<br>\n";
        $msg .= "\t<br>\n";
        $msg .= "\tPlease <a href='$url' target='_blank'>login</a>\n";
        $msg .= "\tand <b>accept</b> if you would like to play.\n";
        $msg .= "</p>\n";

        qwikEmail($email, $subject, $msg, $pid, $token);
        logEmail('invite', $pid, $game, $venueName);
    }


    function emailConfirm($mid){
        $match = $this->matchID($mid);
        $datetime = $match->dateTime();
        $time = date_format($datetime, "ga D");
        $game = $match->game();
        $pid = $this->id();
        $venueName = $match->venueName();
        $url = loginURL(self::DAY);

        $subject = "Confirmed: $game $time at $venueName";

        $msg  = "<p>\n";
        $msg .= "\tYour game of <b>$game</b> is set for <b>$time</b> at $venueName.<br>\n";
        $msg .= "\t<br>\n";
        $msg .= "\tIf you need to cancel for some reason, please ";
        $msg .= "<a href='$url' target='_blank'>login</a> ";
        $msg .= "as soon as possible to let your rival know.\n";
        $msg .= "</p>\n";
        $msg .= "<p>\n";
        $msg .= "\t<b>Good Luck! and have a great game.</b>\n";
        $msg .= "</p>\n";

        qwikEmail($this->email(), $subject, $msg, $pid, $token);
        logEmail('confirm', $pid, $game, $venueName, $time);
    }



    function emailMsg($message, $match){
        global $games;

        $datetime = $match->dateTime();
        $time = date_format($datetime, "ha D");
        $game = $match['game'];
        $gameName = $games["$game"];
        $pid = $this->id();
        $token = $this->token(2*Player::DAY);
        $venueName = shortVenueID($match->venue());
        $url = loginURL(self::DAY);

        $subject = 'Message from qwikgame rival';

        $msg  = "<p>\n";
        $msg .= "<b>$gameName</b> at $time at $venueName<br><br><br>";
        $msg .= "\tYour rival says: \"<i>$message</i>\"<br><br><br>\n";
        $msg .= "Please <a href='$url'>login</a> to reply.";
        $msg .= "</p>\n";

        qwikEmail($this->email(), $subject, $msg, $pid, $token);
    }


    function emailCancel($match){
        $datetime = $match->dateTime();
        $time = date_format($datetime, "ha D");
        $game = $match->game();
        $pid = $this->id();
        $token = $player->token(2*self::DAY);
        $vid = $match->vid();
        $venueName = $match->venueName();

        $subject = "Cancelled: $game $time at $venueName";

        $msg  = "<p>\n";
        $msg .= "\tYour game of <b>$game</b> at <b>$time</b> at $venuName has been CANCELLED by your rival.<br>\n";
        $msg .= "</p>\n";

        qwikEmail($this->email(), $subject, $msg, $pid, $token);
        logEmail('cancel', $pid, $game, $venueName, $time);
    }


    function emailQuit($player){
        global $YEAR;
        $lang = $this->lang();

        $subject = $GLOBALS[$lang]["emailQuitSubject"];
        $msg = $GLOBALS[$lang]["emailQuitBody"];
        $pid = $this->id();
        $token = $this->token(self::YEAR);

        qwikEmail($this->email(), $subject, $msg, $pid, $token);
        logEmail('quit', $pid);
    }








////////// OUTCOME //////////////////////////////////////////

    public function outcomeAdd($mid, $parity, $rep){
        $match = new Match($this, $this->matchID($mid));
        if (isset($match)){
            $match->status('history');

            $outcome = $this->xml->addChild('outcome', '');
            $outcome->addAttribute('game', $match->game());
            $outcome->addAttribute('rival', $match->rid());
            $date = $match->dateTime();
            $outcome->addAttribute('date', $date->format("d-m-Y"));
            $outcome->addAttribute('parity', $parity);
            $outcome->addAttribute('rep', $rep);
            $outcome->addAttribute('rely', $this->xml->rely['val']); //default value
            $outcome->addAttribute('id', $mid);

            return new Player($match->rid(), $this->log);
        } else {
            header("Location: error.php?msg=unable to locate match.");
        }
    }


    // update the reputation records for a player
    public function updateRep($feedback){
        $rep = null !== $this->rep() ? $this->rep() : $this->xml->addChild('rep', '');
        switch ($feedback){
            case '+1':
                $rep['pos'] = $rep['pos'] + 1;
                break;
            case '-1':
                $rep['neg'] = $rep['neg'] + 1;
                break;
        }
    }



////////// UPLOAD //////////////////////////////////////////


    public function uploadIDs(){
        return $this->xml->xpath("upload");
    }


    public function uploadAdd($fileName){
        $up = $this->xml->addChild('upload', $fileName);
//      $up->addAttribute('date', date_format(date_create(), 'Y-m-d'));
    }
    


    public function rankingGet($fileName){
    //echo "<br>GETUPLOAD<br>";
        $ranking = new Ranking($fileName, $this->log);
        if(!$ranking){
            $missing = $this->xml->xpath("/player/upload[text()='$fileName']");
            foreach($missing as $miss){
                $this->removeElement($miss);
            }
            return FALSE;
        }
        return $ranking;
    }


    public function rankingDelete($fileName){
        $path = self::PATH_UPLOAD;
        deleteFile("$path/$fileName.csv");
        deleteFile("$path/$fileName.xml");

        $delete = $this->xml->xpath("/player/upload[text()='$fileName']");
       foreach($delete as $del){
           $this->removeElement($del);
       }
    }


    public function rankingActivate($fileName){
        $ranking = $this->rankingGet($fileName);
        $ranking->insert($this->log);
    }


    public function rankingDeactivate($fileName){
        $ranking = $this->rankingGet($fileName);
        $ranking->extract($this->log);
    }

/********************************************************************************
Processes a user request to upload a set of player rankings and
Returns a status message

$player XML     player data for the player uploading the rankings
$game    String    The game in the uploaded rankings
$title    String    A player provided title for the rankings

A player can upload a file containing player rankings which qwikgame can
utilize to infer relative player ability. A comma delimited file is
required containing the rank and sha256 hash of each player's email address.

A set of rankings has a status: [ uploaded | active ]

Requirements:
1.    Every line must contain an integer rank and the sha256 hash of an email
    address separated by a comma.
    18 , d6ef13d04aee9a11ad718cffe012bf2a134ca1c72e8fd434b768e8411c242fe9
2.    The first line of the uploaded file must contain the sha256 hash of
    facilitator@qwikgame.org with rank=0. This provides a basic check that
    the sha256 hashes in the file are compatible with those in use at qwik game org.
3.    The file size must not exceed 200k (or about 2000 ranks).

********************************************************************************/
    function rankingUpload($game, $title){
        global $tick;
        $ok = TRUE;
        $msg = "<b>Thank you</b><br>";

        $fileName = $_FILES["filename"]["name"];
        if (strlen($fileName) > 0){
            $msg .= "$fileName<br>";
        } else {
            $msg .= "missing filename";
            $ok = FALSE;
        }

        if($ok && $_FILES["filename"]["size"] > 200000){
            $msg .= 'Max file size (100k) exceeded.';
            $ok = FALSE;
        }

        $date = date_create();
        $tmp_name = $_FILES["filename"]["tmp_name"];
        $fileName = $game . "RankUpload" . $date->format('Y:m:d:H:i:s');
        $path = self::PATH_UPLOAD . "/" . $fileName . Ranking::CSV;
        $this->moveUpload($tmp_name, $path);

        $ranking = importRanking($game, $path, $fileName);
        $ok = $ranking->valid;
        $msg .= $ranking->transcript;

        $ranking->attribute("title", $title);
        $ranking->attribute("uploadName", $uploadName);

        if ($ok){
            $existingCount = 0;
            foreach($ranks as $sha256){
                if (file_exists("player/$sha256.xml")){
                    $existingCount++;
                }
            }
            $msg .= "$existingCount players have existing qwikgame records.<br>";
        }

        if($ok){
            $msg .= "<br>Click $tick to activate these rankings";
        } else {
            $msg .= "<br>Please try again.<br>";
        }
        return $msg;
    }


    public function importRanking($game, $path, $fileName){
        $ranking = new Ranking($fileName, $this->log, $game, $path);
        if ($ranking->valid){
            $ranking->attribute("player", $this->id());
            $ranking->attribute('uploadHash', hash_file('sha256', $path));
            $this->uploadAdd($game, $fileName);
            return $ranking;
        }
        return null;
    }


    public function repWord(){

        $rep = $this->rep();
        $repPos = intval($rep['pos']);
        $repNeg = intval($rep['neg']);
        $repTot = $repPos + $repNeg;

        if($repTot <= 0){
            return;
        } elseif($repTot < 5){
            if($repPos > $repNeg){
                $word = '<t>good</t>';
            } elseif($repPos < $repNeg){
                $word = '<t>poor</t>';
            } else {
                $word = '<t>mixed</t>';
            }
        } else {
            $pct = $repPos/$repTot;
            if($pct >= 0.98){            // 1:50
                $word = '<t>supurb</t>';
            } elseif($pct > 0.95){        // 1:20
                $word = '<t>excellent</t>';
           } elseif($pct >= 0.90){     // 1:10
                $word = '<t>great</t>';
            } elseif($pct >= 0.80){        // 1:5
                $word = '<t>good</t>';
            } elseif ($pct >= 0.66){    // 1:3
            $word = '<t>mixed</t>';
            } elseif ($pct >= 0.50){    // 1:2
                $word = '<t>poor</t>';
            } else {
                $word = '<t>dreadful</t>';
            }
        }
        return $word;
    }




    public function repFraction(){
        $rep = $this->rep();
        $repPos = intval($rep['pos']);
        $repNeg = intval($rep['neg']);
        $repTot = $repPos + $repNeg;
        $thumb = "<span class='fa fa-thumbs-o-up green'></span>";
        return "$repPos $thumb / $repTot";
    }


    function repThumbs(){
        $rep = $this->rep();
        $repPos = intval($rep['pos']);
        $repNeg = intval($rep['neg']);
        $thumbUp = "<span class='fa fa-thumbs-o-up green'></span>";
        $thumbDown = "<span class='fa fa-thumbs-o-down red'></span>";
        return str_repeat($thumbDown, $repNeg) . str_repeat($thumbUp, $repPos);
    }






////////// PARITY //////////////////////////////////////////


    /********************************************************************************
    Returns an estimate of the parity of two players for a given $game.
    A positive parity indicates that $player is stronger than $rival.

    $player    XML        player data for player #1
    $rival    XML        player data for player #2
    $game    String    A string ID of a game.

    Each player is "related" to other players by the reports they have made of the
    other player's relative ability in a game (ie player-A reports that A>B A=C A<D A>>E).
    This sphere of relations is referred to as the players *orb*.

    A players *orb* can be *expanded* to include secondary relationships
    (ie B=C B>E C=A D=F A>>F) and so on for 3rd, 4th & 5th degree relationships and so on.

    The parity estimate is made by expanding the orbs of both players until there is an
    overlap, and then using these relationships to estimate the parity between the two players.
    For example there is no overlap between
        Orb-A = (A>B A=C A<D A>>E)
        Orb-F = (F=G F>H)
    but if both orbs are expanded then there is an overlap
        Orb-A = (A>B A=C A<D A>>E B=G C=I C>J C>K D>H)
        Orb-F = (F=G F>H G=B H=L)
    and the following relationships are used to estimate parity between player-A and player-F
        A>B B=G F=G G=B A<D D>H F>H


    Note that each player's orb can be traversed outwards from one report to the next;
    but not in inwards direction (of course there are loops). Function crumbs() is called to     construct bread-crumb trails back to the center.
    ********************************************************************************/
    public function parity($rival, $game){
//echo "<br>PARITY $game<br>\n";

    $playerID = $this->id();
    $rivalID = $rival->id();
//echo "player: $playerID<br>\n";
//echo "rival: $rivalID<br>\n";

    // obtain the direct orb for each of the players
    $playerOrb = $this->orb($game);
    $rivalOrb = $rival->orb($game);

    // generate 'bread-crumb' trails for both orbs
    $playerOrbCrumbs = $playerOrb->crumbs($playerID);
    $rivalOrbCrumbs = $rivalOrb->crumbs($rivalID);

    // compute the intersection between the two orbs
    $orbIntersect = array_intersect(
                        array_keys($playerOrbCrumbs),
                        array_keys($rivalOrbCrumbs)
                    );

    // check if the orbs are isolated (ie no possible further expansion)
    $playerOrbSize = count($playerOrbCrumbs);
    $rivalOrbSize = count($rivalOrbCrumbs);
    $playerIsolated = FALSE;
    $rivalIsolated = FALSE;
    $flipflop = FALSE;
    while (!($playerIsolated && $rivalIsolated)
        && count($orbIntersect) < 3
        && ($playerOrbSize + $rivalOrbSize) < 100){

        $members = array();
        $flipflop = !$flipflop;

        // expand one orb and then the other seeking some intersection
        if ($flipflop){
            $prePlayerOrbSize = $playerOrbSize;
            $playerOrbCrumbs = $playerOrb->expand($playerOrbCrumbs, $game);
            $playerOrbSize = count($playerOrbCrumbs);
            $playerIsolated = ($playerOrbSize == $prePlayerOrbSize);
        } else {
            $preRivalOrbSize = $rivalOrbSize;
            $rivalOrbCrumbs = $rivalOrb->expand($rivalOrbCrumbs, $game);
            $rivalOrbSize = count($rivalOrbCrumbs);
            $rivalIsolated = ($rivalOrbSize == $preRivalOrbSize);
        }


        // compute the intersection between the two orbs
        $orbIntersect = array_intersect(
                            array_keys($playerOrbCrumbs),
                            array_keys($rivalOrbCrumbs)
                        );
        $orbIntersect[] = $this->subID($playerID);
        $orbIntersect[] = $this->subID($rivalID);

//echo "playerIsolated=$playerIsolated<br>\n";
//echo "rivalIsolated=$rivalIsolated<br>\n";
//$cmc = count($orbIntersect);
//echo "commonMemberCount=$cmc<br>\n";
//echo "playerOrbSize=$playerOrbSize<br>\n";
//echo "rivalOrbSize=$rivalOrbSize<br><br>\n\n";
    }

//echo "playerOrbCrumbs = ";
//print_r($playerOrbCrumbs);
//echo "<br><br>";

//echo "rivalOrbCrumbs = ";
//print_r($rivalOrbCrumbs);
//echo "<br><br>";


//echo "orbIntersect Crumbs=";
//print_r($orbIntersect);
//echo "<br><br>\n";

//echo "<br><br><br>playerOrb=";
//print_r($playerOrb);
//echo "<br><br><br>\n";

//echo "<br><br><br>rivalOrb=";
//print_r($rivalOrb);
//echo "<br><br><br>\n";


    // prune both orbs back to retain just the paths to the intersection points
    $playerOrb->prune($orbIntersect);
    $rivalOrb->prune($orbIntersect);

//echo "<br><br><br>playerOrb=";
//print_r($playerOrb);
//echo "<br><br><br>\n";

//echo "<br><br><br>rivalOrb=";
//print_r($rivalOrb);
//echo "<br><br><br>\n";

   $invRivalOrb = $rivalOrb->inv($rivalID);
   $spliceOrb = $playerOrb->splice($playerID, $invRivalOrb);

//echo "<br><br><br>splicedOrb=";
//print_r($spliceOrb);
//echo "<br><br><br>\n";
    $parity = $spliceOrb->parity($rivalID);

    $this->logMsg("parity ".snip($playerID)." ".snip($rivalID)." $parity". $playerOrb->print());

    return $parity;

}



// signed square of a number
    private function ssq($n){
    return gmp_sign($n) * $n * $n;
}



    private function subID($id){
return (string)$id;
    return substr("$id",0, 10);
}



    /********************************************************************************
    Returns the player orb extended to include only the direct ability reports made
    by the player

    $game            String    The game of interest
    $filter            Array   An array of nodes to keep or discard
    $positiveFilter    Boolean    TRUE to include nodes in the $filter; FALSE to discard

    An ORB represents the sphere of PARITY around a PLAYER in a PARITY graph linked by
    estimates from MATCH FEEDBACK, uploaded RANKS, and RECKONS. An ORB is held in an
    associative array of arrays with key=PLAYER-ID and value=array of PARITY link ID's.

    ********************************************************************************/
    public function orb($game, $filter=FALSE, $positiveFilter=FALSE){
    //echo "PLAYERORB $game $playerID<br>\n";
        $orb = new Orb($this->log);
        $parities = $this->xml->xpath("rank[@game='$game'] | reckon[@game='$game'] | outcome[@game='$game']");
        foreach($parities as $par){
            $rid = subID($par['rival']);
            if (!$filter){
                $include=TRUE;
            } elseif($positiveFilter){
                $include = in_array($rid, $filter);
            } else {
                $include = ! in_array($rid, $filter);
            }
            if($include){
                $orb->addNode($rid, $par['parity'], $par['rely'], $par['date']);
            }
        }
        //print_r($orb);
        //echo "<br><br>";
        return $orb;
    }


    function htmlLink(){
    $name = $this->nick();
    if(empty($name)){
        return '';
    }

    $url = $player->url();
    if(empty($url)){
        return $name;
    }

    return "<a href='$url' target='_blank'><b>$name</b></a>";
}




}


?>
