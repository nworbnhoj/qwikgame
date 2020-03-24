<?php

require_once 'Qwik.php';
require_once 'Hours.php';
require_once 'Orb.php';
require_once 'Match.php';
require_once 'Email.php';
require_once 'Ranking.php';
require_once 'Notify.php';


class Player extends Qwik {
    
    
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


    static function exists($pid){
        $XML = self::XML;
        return file_exists(PATH_PLAYER."$pid$XML");
    }


    private $id;
    private $xml;


    /**
    * @throws RuntimeException if construction fails.
    */
    public function __construct($pid, $forge=FALSE){
        parent::__construct();
        $this->id = $pid;
        if (!self::exists($pid) && $forge) {
            $this->xml = $this->newXML($pid);
            $this->save();
            self::logMsg("login: new player " . self::snip($pid));
        }
        if (self::exists($pid)){
            $this->xml = $this->retrieve($this->fileName());
        } else {
            throw new RuntimeException("Player does not exist: $id");
        }
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
        $record .= "<notify default='1'/>";
        $record .= "</player>";
        return new SimpleXMLElement($record);
    }


    /**
    * Saves the Player records to a file named id.xml 
    * @return TRUE if the Payer xml is saved successfully, and FALSE
    * otherwise.
    * @throws RuntimeException if the Player is not saved cleanly.
    */
    public function save(){
        $fileName = $this->fileName();
        if (!self::writeXML($this->xml, PATH_PLAYER, $fileName)){
            throw new RuntimeException("failed to save Player $fileName");
            return FALSE;
        }
        return TRUE;
    }


    /**
    * @throws RuntimeException if the xml cannot be read from file.
    */
    public function retrieve($fileName){
        try {
            $fileName = $this->fileName();
            $xml = self::readXML(PATH_PLAYER, $fileName);
        } catch (RuntimeException $e){
            self::logThrown($e);
            $xml = new SimpleXMLElement("<player/>");
            $id = $this->id;
            throw new RuntimeException("failed to retrieve Player: $id");
        }
        return $xml;
    }


    public function ok(){
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


    public function admin($admin=NULL){
        if (!is_null($admin)){
            $this->xml['admin'] = $admin;
        }
        return (string) $this->xml['admin'];
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
            $xmlEmail = $this->xml->email;
            $newEmail = strtolower($newEmail);
            if(empty($xmlEmail)){
                $this->xml->addChild('email', $newEmail);
            } else {
                $oldEmail = $xmlEmail[0];
                if (strcmp($oldEmail, $newEmail) != 0) {
                    changeEmail($newEmail);
                }
            }
        }

        $xmlEmail = $this->xml->email;
        if (empty($xmlEmail)){
            return NULL;
        } else if (count($xmlEmail) == 1){
            return (string) $xmlEmail[0];
        } else {
            return (string) $xmlEmail[0];
        }
    }


    private function changeEmail($newEmail){
        $newID = Player::anonID($newEmail);
        $oldID = $this->id();
        if (Player::exists($newID)){
            self::logMsg("aborted change PlayerID from $oldID to $newID.");
            return FALSE;
        }

        self::removeElement($this->xml->email[0]);
        $this->xml->addChild('email', $newEmail);
        $this->id = $newID;
        $this->xml['id'] = $newID;

        try { // save Player xml with new ID
            $this->save();
        } catch (RuntimeException $e){
            self::logThrown($e);
            // back out email and id changes
            self::removeElement($this->xml->email[0]);
            $this->xml->addChild('email', $oldEmail);
            $this->id = $oldID;
            $this->xml['id'] = $oldID;
            return FALSE;
        }
        try { // replace old player file with a symlink to the new file
            self::deleteFile(PATH_PLAYER."$oldID.xml");
            symlink(PATH_PLAYER."$newID.xml", PATH_PLAYER."$oldID.xml");
        } catch (RuntimeException $e){
            self::logThrown($e);
            throw new RuntimeException("failed to replace Player $oldID.xml with a symlink.");
            return FALSE;
        }
        return TRUE;
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
        return NULL;
    }


    public function outcome($id, $rely=NULL){
        $outcomes = $this->xml->xpath("outcome[@id='$id']");
        if (empty($outcomes)){
            return NULL;
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
        if($this->ok()){
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


    /**
    * Find any instance of $vid in this Players xml and renames to $newID.
    * @return True is $vid was found in this Players records and was renamed to $newID
    */
    public function venueRename($vid, $newID){
        $changed = FALSE;
        $records = $this->xml->xpath("available[venue='$vid'] | match[venue='$vid']");
        foreach($records as $rec){
            $rec->venue = $newID;
            $changed = TRUE;
        }
        return $changed;
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

        self::removeAtt($this->xml, "nick");
        self::removeAtt($this->xml, "url");
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

    /**
    * @throws RuntimeException if the Friend cannot be created
    */
    public function friend($game, $rivalEmail, $parity){
        $rid = Player::anonID($rivalEmail);

        $existing = $this->xml->xpath("reckon[rival='$rid' and @game='$game']");
        if (isset($existing[0]){ // replace a prior reckon for the same rival & game.
            self::removeElement($existing[0]);
        }

        $reckon = $this->xml->addChild('reckon', '');
        $reckon->addAttribute('rival', $rid);
        $reckon->addAttribute('email', $rivalEmail);
        $reckon->addAttribute('parity', $parity);
        $reckon->addAttribute('game', $game);
        $date = date_create();
        $reckon->addAttribute('date', $date->format("d-m-Y"));
        $reckon->addAttribute('id', self::newID());
        $reckon->addAttribute('rely', $this->rely()); //default value
        try {
            $rival =  new Player($rid, true);
            $rival->save();
        } catch (RuntimeException $e){
            self::logThown($e);
            throw new RuntimeException("failed to retrieve Player $rid");
        }
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
    function FavoriteHours($vid, $game, $day, $parity=NULL){
        $favoriteHours = new Hours();
        $available = $this->xml->xpath("available[venue='$vid' and @game='$game']");
        foreach ($available as $avail){
            if ($this->parityBand($avail->xpath("@parity"), $parity)){
                $hours = $avail->xpath("hrs[@day='$day']");
                foreach ($hours as $hrs){
                    $favHrs = new Hours(intval("$hrs"));
                    $favoriteHours->append($favHrs);
                }
            }
        }
        return $favoriteHours;
    }


    // check rival keeness in the given hours
    function keenHours($vid, $game, $day){
        $keenHours = new Hours();
        $keens = $this->xml->xpath("match[status='keen' and venue='$vid' and game='$game']");
        foreach ($keens as $keen){
            $keenHours->append($keen['hrs']);
        }
        return $keenHours;
    }


    public function availableHours($vid, $game, $day, $parity=NULL){
        $availableHours = $this->favoriteHours($vid, $game, $day, $parity);
        $availableHours->append($this->keenHours($vid, $game, $day));
        return $availableHours;
    }






////////// MATCH //////////////////////////////////////////


    public function matchKeen($game, $venue, $date, $hours, $rids=array()) {
        $match = new Match($this,  $this->xml->addChild('match', ''), 'keen', $game, $venue, $date, $hours);
        $venue->addPlayer($this->id());
        $match->invite($rids);
        return $match;
    }


    public function matchInvite($rivalMatch, $parity=NULL){
        $inviteHours = $rivalMatch->hours();

        $availableHours = $this->availableHours(
            $rivalMatch->vid(),
            $rivalMatch->game(),
            $rivalMatch->dateTime()->format('D'),
            $parity
        );
        $inviteHours->includeOnly($availableHours);

        if (!$inviteHours->purge()){
            $match = $this->matchAdd($rivalMatch, $parity, $inviteHours);
            return $match;
        }
        return NULL;
    }


    /**
    * @throws RuntimeException if the Match cannot be added
    */
    public function matchAdd($rivalMatch, $parity, $inviteHours, $email=NULL){
        $email = is_null($email) ? $this->email() : $email;
        if (is_null($inviteHours) | is_null($email)){
            self::logMsg("matchAdd() unable to add Match");
            return;
        }

        try {
            $rid = $rivalMatch->pid();
            $rival = new Player($rid);
            $match = new Match($this,  $this->xml->addChild('match', ''));
            $match->copy($rivalMatch);
            $match->status('invitation');
            $match->hours($inviteHours);
            $match->addRival($rid, $parity, $rival->repWord(), $rival->nick());
            $this->save();
            $notify = new Notify($this);
            $notify->sendInvite($match, $email);
            return $match;
        } catch (RuntimeException $e){
            self::logThrown($e);
            $id = $this->id();
            throw new RuntimeException("failed to add match for Player $id with Rival $rid");
        }
        return NULL;
    }


    function matchDecline($mid){
        $match = $this->matchID($mid);
        if (isset($match)){
            $match->decline();
            $player->save();
        }
    }


    function matchMsg($mid, $msg){
        $match = $this->matchID($mid);
        if (isset($match)){
            $rival = $match->rival();
            if($rival->ok()){
                $rivalMatch = $rival->matchID($mid);
                if (isset($rivalMatch)){
                    $notify = new Notify($rival);
                    $notify->sendMsg($msg, $rivalMatch);
                }
            }
        }
    }


    public function authURL($shelfLife, $param=NULL){
        $query = is_array($param) ? $param : array();
        $query['pid'] = $this->id();
        $query['token'] = $this->token($shelfLife);
        if(!isset($query['qwik'])){
            $query['qwik'] = 'login';
        }
        return QWIK_URL."match.php?" . http_build_query($query);
    }

    
    public function authLink($shelfLife, $param=NULL){
        $authURL = $this->authURL($shelfLife, $param);
        return "<a href='$authURL'>{login}</a>";
  
    }



    public function notifyXML(){
        $xmlArray = $this->xml->xpath("notify");

        if (is_array($xmlArray) && isset($xmlArray[0])){
            $xml = $xmlArray[0];
        } else {
            $xml = $this->xml->addChild('notify', '');
            $xml->addAttribute('default', '1');
        }

        if (isset($xmlArray[1])){  // integrity check
            $pid = self::snip($this->id());
            self::logMsg("player $pid has duplicate <notify> elements");
        }

        return $xml;
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


    function emailFavorite($req, $email){
        $authLink = $this->authLink(2*self::DAY, $req);
        $paras = array(
            "{Click to confirm}",
            "{Safely ignore}"
        );
        $vars = array(
            "subject"    => "{EmailFavoriteSubject}",
            "paragraphs" => $paras,
            "to"         => $email,
            "gameName"   => self::gameName($req['game']),
            "venue"      => $req['venue'],
            "authLink"   => $authLink
        );
        $email = new Email($vars, $this->lang());
        $email->send();

        $this->logEmail('stash', $this->id());
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


    function emailQuit(){
        $paras = array(
            "{Sorry that you...}",
            "{Your info removed}",
            "{Anon feedback remains}",
            "{Backups remain}",
            "{Good luck}"
        );
        $vars = array(
            "subject"    => "{emailQuitSubject}",
            "paragraphs" => $paras,
            "to"         => $this->email()
        );
        $email = new Email($vars, $this->lang());
        $email->send();
        self::logEmail('quit', $this->id());
    }



////////// OUTCOME //////////////////////////////////////////

    /**
    * @throws RuntimeException if the Match Rival cannot be returned
    */
    public function outcomeAdd($mid, $parity, $rep){
        $match = $this->matchID($mid);
        if (!isset($match)){
            $id = $this->id();
            throw new RuntimeException("failed to find Match $mid for Player $id");
        }
        
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

        try {
            return new Player($match->rid());
        } catch (RuntimeException $e){
            self::logThrown($e);
            $rid = $match->rid();
            throw new RuntimeException("Failed to retrieve Rival $rid for Match $mid");
        }
        return NULL;
    }


    // update the reputation records for a player
    public function updateRep($feedback){
        $rep = NULL !== $this->rep() ? $this->rep() : $this->xml->addChild('rep', '');
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
    


    public function rankingGet($rankingID){
        $ranking = new Ranking($rankingID);
        if(!isset($ranking)){
            $this->rankingDelete($rankingID);
            return FALSE;
        }
        return $ranking;
    }


    public function rankingDelete($fileName){
        self::deleteFile(PATH_UPLOAD."$fileName.csv");
        self::deleteFile(PATH_UPLOAD."$fileName.xml");

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
        $CSV = Ranking::CSV;
        $path = PATH_UPLOAD."$fileName$CSV";
        $this->moveUpload($tmp_name, $path);

        $ranking = importRanking($game, $path, $fileName);
        $ok = $ranking->valid;
        $msg .= $ranking->transcript;

        $ranking->attribute("title", $title);
        $ranking->attribute("uploadName", $uploadName);

        if ($ok){
            $existingCount = 0;
            foreach($ranks as $sha256){
                if (self::exists($sha256)){
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
        return NULL;
    }


    public function removeRanks($id){
        $ranks = $this->xml->xpath("rank[@id='$id']");
        foreach($ranks as $rank){
            self::removeElement($rank);
        }
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

//    self::logMsg("parity ".self::snip($playerID)." ".self::snip($rivalID)." $parity". $playerOrb->chart());

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
        $fileName = "$id.xml";
        return self::deleteFile(PATH_PLAYER."$fileName");
    }


}


?>
