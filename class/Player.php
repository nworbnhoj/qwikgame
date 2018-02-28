<?php

require_once 'Qwik.php';
require_once 'Hours.php';
require_once 'Orb.php';
require_once 'Match.php';
require_once 'Email.php';
require_once 'Ranking.php';


class Player extends Qwik {

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

    public function __construct($pid, $forge=FALSE){
        parent::__construct();
        $this->id = $pid;
        $fileName = $this->fileName();
        $path = self::PATH_PLAYER . "/" . $fileName;
        if (!file_exists($path) && $forge) {
            $this->xml = $this->newXML($pid);
            $this->save();
            self::logMsg("login: new player " . self::snip($pid));
        }
        $this->xml = file_exists($path) ? $this->retrieve($fileName) : NULL ;
    }
    
    
    public function fileName(){
        return $this->id() . self::XML;
    }


    private function newXML(){
        $pid = $this->id();
        $now = new DateTime('now');
        $debut = $now->format('d-m-Y');
        $record  = "<player id='$pid' debut='$debut' lang='en'>";
        $record .= "<rep pos='0' neg='0'/>";
        $record .= "<rely val='1.0'/>";
        $record .= "</player>";
        return new SimpleXMLElement($record);
    }


    public function save(){
        return self::writeXML(
            $this->xml, 
            self::PATH_PLAYER, 
            $this->fileName()
        );
    }


    public function retrieve($fileName){
        return self::readXML( 
            self::PATH_PLAYER, 
            $fileName        
        );
    }


    public function exists(){
        return !is_null($this->xml);
    }
    
    
    /*******************************************************************************
    Returns the sha256 hash of the $email address provided

    $email    String    an email address

    The unique player ID is chosen by taking the sha256 hash of the email address. 
    This has a number of advantages:
    - The player ID will be unique because the email address will be unique
    - Qwikgame can accept and use a sha256 hash to store anonymous player data
    - A new email address can be linked to existing anonymous player data

    *******************************************************************************/
    static function anonID($email){
        return hash('sha256', $email);
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


    public function rely($disparity=NULL){    // note disparity range [0,4]
        $rely = floatval($this->xml->rely['val']);
        if (!is_null($disparity)){
            $rely = $this->expMovingAvg($rely, 4-$disparity, 3);
            $this->xml->rely['val'] = $rely;
        }
        return $rely;
    }


    function rep(){
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
            if(empty($this->email)){
                $this->xml->addChild('email', $newEmail);
            } else {
                $oldEmail = $this->xml->email[0];
                if ($oldEmail != $newEmail) {
                    $newID = Player::anonID($newEmail);
                    if (false){ // if newID already exists then log and abort
                        self::logMsg("abort change email from $oldEmail to $newEmail.");
                    } else {
                        changeEmail($newEmail);
                    }
                }
            }
        }

        $xmlEmail = $this->xml->email[0];
        if (empty($xmlEmail)){
            return null;
        } else if (count($xmlEmail) == 1){
            return (string) $xmlEmail[0];
        } else {
            return (string) $xmlEmail[0];
        }
    }


    private function changeEmail($newEmail){
        $preID = $this->id();
        $newID = Player::anonID($newEmail);
        self::removeElement($this->xml->email[0]);
        $this->xml->addChild('email', $newEmail);
        $this->id = $newID;
        $this->xml['id'] = $newID;

        // replace old player file with a symlink to the new file
        $path = self::PATH_PLAYER;
        self::deleteFile("$path/$preID.xml");
        symlink("$path/$newID.xml", "$path/$preID.xml");
    }



    public function token($term = Player::SECOND){
        $token = self::newToken(10);
        $nekot = $this->xml->addChild('nekot', $this->nekot($token));
        $nekot->addAttribute('exp', time() + $term);
        $this->save();
        return $token;
    }


    /*******************************************************************************
    Returns the sha256 hash of the $token provided

    $token    String    an token

    When it is necessary to send a token to a user (e.g. via email as a proof of 
    identity) then only the sha256 hash of the token is stored by qwikgame.
    This has a number of advantages:
    - the sha256 hash can be computed on presented tokens and validated against the 
    stored hash
    - if the system is compromised then the user held token remain secure.
    *******************************************************************************/
    function nekot($token){
        return hash('sha256', $token);
    }




    public function available(){
        return $this->xml->xpath("available");
    }


    public function matchStatus($status){
        return $this->xml->xpath("match[@status='$status']");
    }


    public function matchID($id){
        $xml = $this->xml->xpath("match[@id='$id']");
        if (is_array($xml) && isset($xml[0])){
            return new Match($this, $xml[0]);
        }
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


    public function matchCancel($id){
        $match = $this->matchID($id);
        $match->cancel();
    }


    public function isValidToken($token){
        $nekot = $this->nekot($token);
        if($this->exists()){
            return count($this->xml->xpath("/player/nekot[text()='$nekot']"))>0;
        }
        return FALSE;
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
    public function deleteData($id){
        $rubbish = $this->xml->xpath("//*[@id='$id']");
        foreach($rubbish as $junk){
            self::removeElement($junk);
        }
    }

    public function quit(){
        $matches = $this->xml->xpath("match[@status!='history']");
        foreach($matches as $xml){
            $match = new Match($this, $xml);
            $match->cancel();
        }
        $records = $this->xml->xpath("available | reckon");
        foreach($records as $record){
            self::removeElement($record);
        }
        $emails = $this->xml->xpath("email");
        foreach($emails as $email){
            self::removeElement($email);
        }

        $this->nick(null);
        $this->url(null);
    }



////////// HELPER /////////////////////////////////////


    // Updates an EXPonential moving AVeraGe with a new data POINT.
    private function expMovingAvg($avg, $point, $exp){
        $avg = floatval($avg);
        $point = floatval($point);
        $exp = intval($exp);
        return ($avg * ($exp-1) + $point) / $exp;
    }



////////// RECKON ///////////////////////////////////////

    public function familiar($game, $rivalEmail, $parity){
        $rid = Player::anonID($rivalEmail);

        $reckon = $this->xml->addChild('reckon', '');
        $reckon->addAttribute('rival', $rid);
        $reckon->addAttribute('email', $rivalEmail);
        $reckon->addAttribute('parity', $parity);
        $reckon->addAttribute('game', $game);
        $date = date_create();
        $reckon->addAttribute('date', $date->format("d-m-Y"));
        $reckon->addAttribute('id', self::newID());
        $reckon->addAttribute('rely', $this->rely()); //default value

        $rival =  new Player($rid, true);
        $rival->save();
    }


    public function region($game, $ability, $region){
        $reckon = $this->xml->addChild('reckon', '');
        $reckon->addAttribute('ability', $ability);
        $reckon->addAttribute('region', $region);
        $reckon->addAttribute('game', $game);
        $date = date_create();
        $reckon->addAttribute('date', $date->format("d-m-Y"));
        $reckon->addAttribute('id', self::newID());
        $reckon->addAttribute('rely', $this->rely()); //default value
    }



////////// AVAILABLE //////////////////////////////////////

    public function availableAdd($game, $vid, $parity, $tz, $all7days, $req){
        $newID = self::newID();
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


    function parityBand($filter, $parity){
        switch ($filter){
            case "matching":
                return ($parity >= -1) && ($parity <= 1);
            case "similar":
                return ($parity >= -2) && ($parity <= 2);
            default:
                return TRUE;
        }
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
    function favouriteHours($vid, $game, $day, $parity=null){
        $favouriteHours = new Hours();
        $available = $this->xml->xpath("available[venue='$vid' and @game='$game']");
        foreach ($available as $avail){
            if ($this->parityBand($avail->xpath("@parity"), $parity)){
                $hours = $avail->xpath("hrs[@day='$day']");
                foreach ($hours as $hrs){
                    $favHrs = new Hours(intval("$hrs"));
                    $favouriteHours->include($favHrs);
                }
            }
        }
        return $favouriteHours;
    }


    // check rival keeness in the given hours
    function keenHours($vid, $game, $day){
        $keenHours = new Hours();
        $keens = $this->xml->xpath("match[status='keen' and venue='$vid' and game='$game']");
        foreach ($keens as $keen){
            $keenHours->include($keen['hrs']);
        }
        return $keenHours;
    }


    public function availableHours($vid, $game, $day, $parity=null){
        $availableHours = $this->favouriteHours($vid, $game, $day, $parity);
        $availableHours->include($this->keenHours($vid, $game, $day));
        return $availableHours;
    }






////////// MATCH //////////////////////////////////////////

    private function newMatch(){
        $match = new Match(
            $this, 
            $this->xml->addChild('match', '')
        );
        return $match;
    }


    public function matchKeen($game, $venue, $date, $hours, $rids=array()) {
        $match = $this->newMatch();
        $match->init('keen', $game, $venue, $date, $hours);
        $venue->addPlayer($this->id());
        $match->invite($rids);
        return $match;
    }


    public function matchInvite($rivalMatch, $parity=null){
        $inviteHours = $rivalMatch->hours();

        $availableHours = $this->availableHours(
            $rivalMatch->vid(),
            $rivalMatch->game(),
            $rivalMatch->dateTime()->format('D'),
            $parity
        );
        $inviteHours->includeOnly($availableHours);

        if (!$inviteHours->empty()){
            $match = $this->matchAdd($rivalMatch, $parity, $inviteHours);
            return $match;
        }
        return null;
    }


    public function matchAdd($rivalMatch, $parity, $inviteHours, $email=null){
        $email = is_null($email) ? $this->email() : $email;
        if (is_null($inviteHours) | is_null($email)){
            self::logMsg("matchAdd() unable to add Match");
            return;
        }

        $rid = $rivalMatch->pid();
        $rival = new Player($rid);
        $match = $this->newMatch();
        $match->copy($rivalMatch);
        $match->status('invitation');
        $match->hours($inviteHours);
        $match->addRival($rid, $parity, $rival->repWord(), $rival->nick());
        $this->save();
        $this->emailInvite($match, $email);
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


    public function authURL($shelfLife, $param=null){
        $query = is_array($param) ? $param : array();
        $query['qwik'] = 'login';
        $query['pid'] = $this->id();
        $query['token'] = $this->token($shelfLife);
        return Page::QWIK_URL."/player.php?" . http_build_query($query);
    }

    
    public function authLink($shelfLife, $param=null){
        $authURL = $this->authURL($shelfLife, $param);
        return "<a href='$authURL'>{login}</a>";
  
    }


    public function emailWelcome($email){
        $authLink = $this->authLink(self::MONTH, array("email"=>$email));
        $paras = array(
            "{Please activate}",
            "{Safely ignore}"
        );
        $vars = array(
            "subject"    => "{EmailWelcomeSubject}",
            "paragraphs" => $paras,
            "to"         => $email,
            "authLink"   => $authLink
        );
        $email = new Email($vars, $this->lang());
        $email->send();

        self::logEmail('welcome', $this->id());
    }


    public function emailLogin(){
        $paras = array(
            "{Click to login}",
            "{Safely ignore}"
        );
        $vars = array(
            "subject"    => "{EmailLoginSubject}",
            "paragraphs" => $paras,
            "to"         => $this->email(),
            "authLink"   => $this->authLink(self::DAY)
        );
        $email = new Email($vars, $this->lang());
        $email->send();

        self::logEmail('login', $this->id());
    }


    function emailFavourite($req, $email){
        $authLink = $this->authLink(2*self::DAY, array("email"=>$email));
        $paras = array(
            "{Click to confirm}",
            "{Safely ignore}"
        );
        $vars = array(
            "subject"    => "{EmailFavouriteSubject}",
            "paragraphs" => $paras,
            "to"         => $this->email(),
            "game"       => $req['game'],
            "venueName"  => $req['venue'],
            "authLink"   => $authLink
        );
        $email = new Email($vars, $this->lang());
        $email->send();

        $this->logEmail('stash', $this->id());
    }


    function emailInvite($match, $email=null){
        $email = is_null($email) ? $this->email() : $email ;
        $date = $match->dateTime();
        $day = $match->mday();
        $game = $match->game();
        $venueName = $match->venueName();
        $authLink = $this->authLink(self::WEEK, array("email"=>$email));
        $paras = array(
            "{You are invited}",
            "{Please accept}"
        );
        $vars = array(
            "subject"    => "{EmailInviteSubject}",
            "paragraphs" => $paras,
            "to"         => $email,
            "game"       => $game,
            "day"        => $day,
            "venueName"  => $venueName,
            "authLink"   => $authLink
        );
        $email = new Email($vars, $this->lang());
        $email->send();
        self::logEmail('invite', $this->id(), $game, $venueName);
    }


    function emailConfirm($mid){
        $match = $this->matchID($mid);
        $datetime = $match->dateTime();
        $time = date_format($datetime, "ga D");
        $game = $match->game();
        $venueName = $match->venueName();
        $paras = array(
            "{Game is set}",
            "{Need to cancel}",
            "{Have great game}"
        );
        $vars = array(
            "subject"    => "{EmailConfirmSubject}",
            "paragraphs" => $paras,
            "to"         => $this->email(),
            "game"       => $game,
            "time"       => $time,
            "venueName"  => $venueName,
            "authLink"   => $this->authLink(self::DAY)
        );
        $email = new Email($vars, $this->lang());
        $email->send();

        self::logEmail('confirm', $this->id(), $game, $venueName, $time);
    }


    private function emailChange($email){
        $paras = array(
            "{Click to change}",
            "{Safely ignore}"
        );
        $vars = array(
            "subject"    => "{EmailChangeSubject}",
            "paragraphs" => $paras,
            "to"         => $this->email(),
            "email"      => $email,
            "authLink"   => $this->authLink(self::DAY)
        );
        $email = new Email($vars, $this->lang());
        $email->send();

        self::logEmail('email', $this->id());
    }



    function emailMsg($message, $match){
        $datetime = $match->dateTime();
        $time = date_format($datetime, "ga D");
        $game = $match->game();
        $gameName = self::qwikGames()["$game"];
        $pid = $this->id();
        $venueName = Venue::svid($match->venue());
        $paras = array(
            "{game time venue}",
            "{Your rival says...}",
            "{Please reply}"
        );
        $vars = array(
            "subject"    => "{EmailMsgSubject}",
            "paragraphs" => $paras,
            "to"         => $this->email(),
            "message"    => $message,
            "game"       => $game,
            "time"       => $time,
            "venueName"  => $venueName,
            "authLink"   => $this->authLink(self::DAY)
        );
        $email = new Email($vars, $this->lang());
        $email->send();

        self::logEmail('msg', $pid, $game, $venueName, $time);
    }


    function emailCancel($match){
        $datetime = $match->dateTime();
        $time = date_format($datetime, "ga D");
        $game = $match->game();
        $pid = $this->id();
        $venueName = $match->venueName();
        $vars = array(
            "subject"    => "{EmailCancelSubject}",
            "paragraphs" => array("{Game cancelled}"),
            "to"         => $this->email(),
            "game"       => $game,
            "time"       => $time,
            "venueName"  => $venueName
        );
        $email = new Email($vars, $this->lang());
        $email->send();
        self::logEmail('cancel', $pid, $game, $venueName, $time);
    }


    function emailQuit(){
        $paras = array(
            "{Sorry that you...}",
            "{Your info removed}",
            "{Anon feedback remains}",
            "{Backups remain}",
            "{Good luck}"
        );
        $vars = array(
            "subject"    => "{EmailQuitSubject}",
            "paragraphs" => $paras,
            "to"         => $this->email()
        );
        $email = new Email($vars, $this->lang());
        $email->send();
        self::logEmail('quit', $this->id());
    }



////////// OUTCOME //////////////////////////////////////////

    public function outcomeAdd($mid, $parity, $rep){
        $match = $this->matchID($mid);
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

            return new Player($match->rid());
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
        $ranking = new Ranking($fileName);
        if(!isset($ranking)){
            $this->rankingDelete($fileName);
            return FALSE;
        }
        return $ranking;
    }


    public function rankingDelete($fileName){
        $path = self::PATH_UPLOAD;
        self::deleteFile("$path/$fileName.csv");
        self::deleteFile("$path/$fileName.xml");

        $delete = $this->xml->xpath("/player/upload[text()='$fileName']");
       foreach($delete as $del){
           self::removeElement($del);
       }
       $this->logMsg("Deleted Ranking $fileName");
    }


    public function rankingActivate($fileName){
        $ranking = $this->rankingGet($fileName);
        $ranking->insert();
    }


    public function rankingDeactivate($fileName){
        $ranking = $this->rankingGet($fileName);
        $ranking->extract();
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
        $ranking = new Ranking($fileName, $game, $path);
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
            return '{good}';
        } elseif($repTot < 5){
            if($repPos > $repNeg){
                $word = '{good}';
            } elseif($repPos < $repNeg){
                $word = '{poor}';
            } else {
                $word = '{mixed}';
            }
        } else {
            $pct = $repPos/$repTot;
            if($pct >= 0.98){            // 1:50
                $word = '{supurb}';
            } elseif($pct > 0.95){        // 1:20
                $word = '{excellent}';
           } elseif($pct >= 0.90){     // 1:10
                $word = '{great}';
            } elseif($pct >= 0.80){        // 1:5
                $word = '{good}';
            } elseif ($pct >= 0.66){    // 1:3
            $word = '{mixed}';
            } elseif ($pct >= 0.50){    // 1:2
                $word = '{poor}';
            } else {
                $word = '{dreadful}';
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

    Each player is "related" to other players by the reports they have
    made of the other player's relative ability in a game 
    (ie player-A reports that A>B A=C A<D A>>E).
    This sphere of relations is referred to as the players *orb*.

    A players *orb* can be *expanded* to include secondary relationships
    (ie B=C B>E C=A D=F A>>F) and so on for 3rd, 4th & 5th degree relationships
    and so on.

    The parity estimate is made by expanding the orbs of both players until there
    is an overlap, and then using these relationships to estimate the parity between
    the two players.
    For example there is no overlap between
        Orb-A = (A>B A=C A<D A>>E)
        Orb-F = (F=G F>H)
    but if both orbs are expanded then there is an overlap
        Orb-A = (A>B A=C A<D A>>E B=G C=I C>J C>K D>H)
        Orb-F = (F=G F>H G=B H=L)
    and the following relationships are used to estimate parity between player-A 
    and player-F
        A>B B=G F=G G=B A<D D>H F>H


    Note that each player's orb can be traversed outwards from one report to the
    next; but not in inwards direction (of course there are loops). Function 
    crumbs() is called to construct bread-crumb trails back to the center.
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
    $playerOrbCrumbs = $playerOrb->crumbs($playerID, $playerID);
    $rivalOrbCrumbs = $rivalOrb->crumbs($rivalID, $rivalID);

    // compute the intersection between the two orbs
    $orbIntersect = array_intersect(
                        array_keys($playerOrbCrumbs),
                        array_keys($rivalOrbCrumbs)
                    );

    // check if the orbs are isolated (ie no possible further expansion)
    $playerOrbSize = count($playerOrbCrumbs);
    $rivalOrbSize = count($rivalOrbCrumbs);
    $playerIsolated = $playerOrbSize == 0;
    $rivalIsolated = $rivalOrbSize == 0;
    $flipflop = FALSE;

    while (!($playerIsolated && $rivalIsolated)
    && count($orbIntersect) < 3
    && ($playerOrbSize + $rivalOrbSize) < 100){

        $members = array();
        $flipflop = !$flipflop;

        // expand one orb and then the other seeking some intersection
        if ($flipflop){
            $prePlayerOrbSize = $playerOrbSize;
            $playerOrbCrumbs = $playerOrb->expand($playerOrbCrumbs);
            $playerOrbSize = count($playerOrbCrumbs);
            $playerIsolated = ($playerOrbSize == $prePlayerOrbSize);
        } else {
            $preRivalOrbSize = $rivalOrbSize;
            $rivalOrbCrumbs = $rivalOrb->expand($rivalOrbCrumbs);
            $rivalOrbSize = count($rivalOrbCrumbs);
            $rivalIsolated = ($rivalOrbSize == $preRivalOrbSize);
        }


        // compute the intersection between the two orbs
        $orbIntersect = array_intersect(
                            array_keys($playerOrbCrumbs),
                            array_keys($rivalOrbCrumbs)
                        );

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
    $playerOrb = $playerOrb->prune($orbIntersect);
    $rivalOrb = $rivalOrb->prune($orbIntersect);

//print_r($orbIntersect);
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

//    self::logMsg("parity ".self::snip($playerID)." ".self::snip($rivalID)." $parity". $playerOrb->print());

    return $parity;

}



// signed square of a number
    private function ssq($n){
    return gmp_sign($n) * $n * $n;
}



    static public function subID($id){
        return (string)$id;
        //return substr("$id",0, 10);
    }



    /********************************************************************************
    Returns the player orb extended to include only the direct ability reports made
    by the player

    $game            String   The game of interest
    $filter          Array    An array of nodes to keep or discard
    $positiveFilter  Boolean  TRUE to include nodes in the $filter; FALSE to discard

    An ORB represents the sphere of PARITY around a PLAYER in a PARITY graph linked by
    estimates from MATCH FEEDBACK, uploaded RANKS, and RECKONS. An ORB is held in an
    associative array of arrays with key=PLAYER-ID and value=array of PARITY link ID's.

    ********************************************************************************/
    public function orb($game, $filter=FALSE, $positiveFilter=FALSE){
    //echo "PLAYERORB $game $playerID<br>\n";
        $orb = new Orb($game);
        $parities = $this->xml->xpath("rank[@game='$game'] | reckon[@game='$game'] | outcome[@game='$game']");
        foreach($parities as $par){
            $rid = self::subID($par['rival']);
            if(!is_null($rid)){
                if (!$filter){
                    $include=TRUE;
                } elseif($positiveFilter){
                    $include = in_array($rid, $filter);
                } else {
                    $include = ! in_array($rid, $filter);
                }
                if($include){
                    $orb->addNode(
                        $rid,
                        $par['parity'],
                        $par['rely'],
                        $par['date']
                    );
                }
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

        $url = $this->url();
        if(empty($url)){
            return $name;
        }

        return "<a href='$url' target='_blank'><b>$name</b></a>";
    }



    public function delete(){
        self::removePlayer($this->id());
    }


    static public function removePlayer($id){
        $path = self::PATH_PLAYER;
        $fileName = "$id.xml";
        return self::deleteFile("$path/$fileName");
    }


}


?>
