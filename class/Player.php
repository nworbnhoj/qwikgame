<?php

require_once 'User.php';
require_once 'Hours.php';
require_once 'Orb.php';
require_once 'Match.php';
require_once 'Ranking.php';


class Player extends User {

    const DEFAULT_PLAYER_XML = 
   "<?xml version='1.0' encoding='UTF-8'?>
    <player lang='en' ok='true'>
      <rep pos='0' neg='0'/>
      <rely val='1.0'/>
      <notify/>
    </player>";
    

    /**
    * @throws RuntimeException if construction fails.
    */
    public function __construct($pid, $forge=FALSE){
        parent::__construct($pid, $forge);
        self::logMsg("player new $pid");
    }


    public function default_xml(){
        return self::DEFAULT_PLAYER_XML;
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
        return $this->xml->xpath("rep")[0];
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


    public function rankAdd($id, $game){
        $this->removeRanks($id);
        $rank = $this->xml->addChild('rank', '');
        $rank->addAttribute('id', $id);
        $rank->addAttribute('game', $game);
    }

        
    public function reckon($id){ 
        return $this->xml->xpath("reckon[@$id]");
    }


    public function friends(){
        $emails = array();
        $reckoning = $this->xml->xpath("reckon[@email]");
        foreach($reckoning as $reckon){
            $email = (string) $reckon['email'];
            $nick = $email; //default
            $anonID = Player::anonID($email);
            if (Player::exists($anonID)){
                $friend = new Player($anonID);
                if ($friend->ok()){
                    $nic = $friend->nick();
                    $nick = empty($nic) ? $email : $nic ;
                }
            }             
            $emails[$email] = $nick;
        }
        return $emails;
    }
    
       
    public function favoriteVenues($game){
        $available = $this->xml->xpath("available[@game='$game']");
        $venues = array();
        foreach($available as $avail){
            $vid = $avail->venue;
            $name = explode("|", $vid)[0];
            $venues["$vid"] = "$name";
        }
        return $venues;
    }
    
       
    public function matchVenues($game){
        $available = $this->xml->xpath("match[@game='$game']");
        $venues = array();
        foreach($available as $avail){
            $vid = $avail->venue;
            $name = explode("|", $vid)[0];
            $venues["$vid"] = "$name";
        }
        return $venues;
    }
    

    public function matchQuery($query){
        return $this->xml->xpath("$query");
    }


    public function matchCancel($id){
        $match = $this->matchID($id);
        if (isset($match)){
            $match->cancel();
            $this->save();           
            $venue = new Venue($match->vid());
            $venue->matchCancel($id);
            $venue->save(TRUE);
        }
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


    public function quit(){
        foreach($this->xml->xpath("match[@status='cancelled']") as $xml){
            self::removeElement($xml);
        }
        foreach($this->xml->xpath("match[@status='feedback']") as $xml){
            self::removeElement($xml);
        }
        foreach($this->xml->xpath("match[@status!='history']") as $xml){
            $match = new Match($this, $xml);
            $match->cancel();
        }
        foreach($this->xml->xpath("available") as $xml){
            self::removeElement($xml);
        }
        foreach($this->xml->xpath("reckon") as $xml){
            self::removeAtt($xml, "email");
        }
        
        parent::quit();
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
        $id = self::newID();
        $rid = Player::anonID($rivalEmail);

        $existing = $this->xml->xpath("reckon[rival='$rid' and @game='$game']");
        if (isset($existing[0])){ // replace a prior reckon for the same rival & game.
            self::removeElement($existing[0]);
        }

        $reckon = $this->xml->addChild('reckon', '');
        $reckon->addAttribute('rival', $rid);
        $reckon->addAttribute('email', $rivalEmail);
        $reckon->addAttribute('parity', $parity);
        $reckon->addAttribute('game', $game);
        $date = date_create();
        $reckon->addAttribute('date', $date->format("d-m-Y"));
        $reckon->addAttribute('id', $id);
        $reckon->addAttribute('rely', $this->rely()); //default value
        try {
            $rival =  new Player($rid, true);
            $rival->save();
        } catch (RuntimeException $e){
            self::logThown($e);
            throw new RuntimeException("failed to retrieve Player $rid");
        }
        return $id;
    }


    public function region($game, $parity, $region){
        $reckon = $this->xml->addChild('reckon', '');
        $reckon->addAttribute('parity', $parity);
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
        $v = $element->addChild('venue', htmlspecialchars($vid));
        $v->addAttribute('tz', $tz);
        $days = array('Sun', 'Mon', 'Tue','Wed', 'Thu', 'Fri', 'Sat');
        foreach($days as $day){
            $requestHrs = $all7days;
            if (!$requestHrs && isset($req[$day])) {
                $requestHrs = $req[$day];
            }
            if ($requestHrs) {
                $hrs = $element->addChild('hrs', htmlspecialchars($requestHrs));
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
        $keens = $this->xml->xpath("match[@status='keen' and venue='$vid' and @game='$game']");
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
        $venue->matchAdd($this->id(), $match->id(), $game, $date, $hours);
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
            $pid = $this->id();
            $venue = new Venue($match->vid());
            $venue->matchAdd($pid, $match->id(), $match->game(), $match->date(), $match->hours());
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


    function matchAccept($mid, $hour){
        $match = $this->matchID($mid);
        if (isset($match)){
            $mid = $match->accept(new Hours($hour));
            $this->save();
        } else {
            self::logMsg("unable to locate match: $mid   $this->id()");
            $mid = NULL;         
        }
        return $mid;
    }


    function matchDecline($mid){
        $match = $this->matchID($mid);
        if (isset($match)){
            $match->decline();
            $this->save();
        }
    }


    function matchMsg($mid, $msg){
        $match = $this->matchID($mid);
        if (isset($match)){
            $match->chat($msg, Match::CHAT_ME);
            $rival = $match->rival();
            if($rival->ok()){
                $rivalMatch = $rival->matchID($mid);
                if (isset($rivalMatch)){
                    $rivalMatch->chat($msg, Match::CHAT_YU);
                    $notify = new Notify($rival);
                    $notify->sendMsg($msg, $rivalMatch);
                }
                $rival->save();
            }
        }
    }


    public function authURL($shelfLife, $target='match.php', $param=NULL){
        return parent::authURL($shelfLife, $target, $param);
    }

    
    public function authLink($shelfLife, $target='match.php', $param=NULL){
        return parent::authLink($shelfLife, $target, $param);
    }


    public function emailWelcome($email, $req, $target='match.php'){
        return parent::emailWelcome($email, $req, $target);
    }


    public function emailFavorite($req, $email){
        $authLink = $this->authLink(2*self::DAY, 'favorite.php', $req);
        $paras = array(
            "{Click to confirm}",
            "{Safely ignore}"
        );
        $vars = array(
            "subject"    => "{EmailFavoriteSubject}",
            "paragraphs" => $paras,
            "to"         => $email,
            "gameName"   => self::gameName($req['game']),
            "venue"      => $req['vid'],
            "authLink"   => $authLink
        );
        $email = new Email($vars, $this->lang());
        $email->send();

        $this->logEmail('stash', $this->id());
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


    public function uploads(){
        return $this->xml->xpath("upload");
    }
    
    public function upload($id){
        $upload = $this->xml->xpath("upload[@id='$id']");
        return (string) $upload[0];
    }


    public function uploadAdd($id, $fileName){
        $up = $this->xml->addChild('upload', htmlspecialchars($fileName));
        $up->addAttribute('id', $id);
//      $up->addAttribute('date', date_format(date_create(), 'Y-m-d'));
    }
    


    public function rankingGet($filename){
        $ranking = new Ranking($filename);
        if(!isset($ranking)){
            $this->rankingDelete($filename);
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
    18,d6ef13d04aee9a11ad718cffe012bf2a134ca1c72e8fd434b768e8411c242fe9
2.    The first line of the uploaded file must contain the sha256 hash of
    facilitator@qwikgame.org with rank=0. This provides a basic check that
    the sha256 hashes in the file are compatible with those in use at qwik game org.
3.    The file size must not exceed 200k (or about 2000 ranks).

********************************************************************************/
    function rankingUpload($game, $title){
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
            $msg .= 'Max file size (200k) exceeded.';
            $ok = FALSE;
        }        

        $tmp_name = $_FILES["filename"]["tmp_name"];
        $invalidLine = Ranking::validate($tmp_name);
        if($ok && $invalidLine > 0){
            $msg .= "File contains an invalid line:\n$invalidLine";
            $ok = FALSE;
        }
        
        
        if ($ok){
          $date = date_create();
          $filename = $game . "RankUpload" . $date->format('Y:m:d:H:i:s');
          move_uploaded_file($tmp_name, PATH_UPLOAD.$filename.self::CSV);

          $ranking = $this->importRanking($filename, $game);
          $ok = $ranking->valid;
          $msg .= $ranking->transcript;

          $ranking->attribute("title", $title);
          $ranking->save();

          $existingCount = 0;
          $ranks = $ranking->ranks();
          foreach($ranks as $sha256 => $rank){
            if (self::exists($sha256)){
              $existingCount++;
            }
          }
          $msg .= "$existingCount players have existing qwikgame records.<br>";
        }

        if($ok){
            $msg .= "<br>You can now activate these rankings";
        } else {
            $msg .= "<br>Please try again.<br>";
        }
        return $msg;
    }


    public function importRanking($filename, $game){
        $ranking = new Ranking($filename, $game);
        if ($ranking->valid){
            $ranking->attribute("player", $this->id());    
            $fqfilename = PATH_UPLOAD.$filename.self::CSV;
            $ranking->attribute('uploadHash', hash_file('sha256', $fqfilename));
            $this->uploadAdd($ranking->id(), $filename);
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
        } else {
            $pct = $repPos/$repTot;
            if      ($repTot>=50 && $pct>=0.98){   // 1:50
                $word = '{superb}';
            } elseif($repTot>=20 && $pct>=0.95){   // 1:20
                $word = '{excellent}';
            } elseif($repTot>=10 && $pct>=0.90){   // 1:10
                $word = '{great}';
            } elseif($repTot>=5  && $pct>=0.80){   // 1:5
                $word = '{good}';
            } elseif($repTot>=5  && $pct>=0.66){   // 1:3
                $word = '{mixed}';
            } elseif($repTot>=5  && $pct>=0.50){   // 1:2
                $word = '{poor}';
            } elseif($repTot>=5  && $pct< 0.50){
                $word = '{dreadful}';
            } elseif($repPos > $repNeg){
                $word = '{good}';
            } elseif($repPos < $repNeg){
                $word = '{poor}';
            } else {
                $word = '{mixed}';
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


    function playerVariables(){
        return array(
            'target'    => 'match.php',
            'reputation'=> repWord(),
        );
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

//echo "Isolated\t$playerIsolated\t$rivalIsolated\n";
//echo "Orb Size\t$playerOrbSize\t$rivalOrbSize\n";
//$cmc = count($orbIntersect);
//echo "commonMemberCount = $cmc\n\n";

    }

//echo "playerOrbCrumbs = ".print_r($playerOrbCrumbs,true);
//echo "rivalOrbCrumbs = ".print_r($rivalOrbCrumbs,true);
//echo "orbIntersect Crumbs=".print_r($orbIntersect,true)."\n";
//echo "\n\n playerOrb=".print_r($playerOrb,true)."\n\n";
//echo "\n\n rivalOrb=".print_r($rivalOrb,true)."\n\n\n";

    // prune both orbs back to retain just the paths to the intersection points
    $playerOrb = $playerOrb->prune($orbIntersect);
    $rivalOrb = $rivalOrb->prune($orbIntersect);

//print_r($orbIntersect);
//echo "\n\nplayerOrb=".print_r($playerOrb,true)."\n\n\n";

//echo "\n\nrivalOrb=".print_r($rivalOrb,true)."\n\n\n";

  // convert any common rank nodes into parity nodes
  $playerRNs = $playerOrb->rankNodes();   // array[rankingID=>array(pid=>Node)]
  $rivalRNs = $rivalOrb->rankNodes();     // array[rankingID=>array(pid=>Node)]
  $rankingIDs = array_intersect(array_keys($playerRNs), array_keys($rivalRNs));
  
// echo "\n\n playerRNs = ".print_r($playerRNs, true)."\n\n";
// echo "\n\n rivalRNs = ".print_r($rivalRNs, true)."\n\n";  
  
  
  foreach($rankingIDs as $rid){           // process each Ranking seperately
    $ranking = new Ranking($rid);
    $date = $ranking->time();
    $ranks = $ranking->ranks();
    $playerRanks = array_intersect_key($ranks, $playerRNs[$rid]);
    $rivalRanks = array_intersect_key($ranks, $rivalRNs[$rid]);
    foreach($rivalRanks as $pid => $rank){         // resolve Player rank Nodes
      $closePid = $this->closestRank($rank, $playerRanks);
      $closeRank = $playerRanks[$closePid];
      $rivalRNs[$rid][$pid]->resolveRank($rank, $closePid, $closeRank, $ranking);
    }
    foreach($playerRanks as $pid => $rank){         // resolve Rival rank Nodes
      $closePid = $this->closestRank($rank, $rivalRanks);
      $closeRank = $rivalRanks[$closePid];
      $playerRNs[$rid][$pid]->resolveRank($rank, $closePid, $closeRank, $ranking);
    }
  }
   

   $invRivalOrb = $rivalOrb->inv($rivalID);
   $spliceOrb = $playerOrb->splice($playerID, $invRivalOrb);

//echo "\n\nsplicedOrb=".print_r($spliceOrb)."\n\n\n";

    $parity = $spliceOrb->parity($rivalID);

//    self::logMsg("parity ".self::snip($playerID)." ".self::snip($rivalID)." $parity". $playerOrb->chart());

    return $parity;

}


  function closestRank($aimRank, $arr) {
    $closeRank = null;
    $closePid = null;
    foreach ($arr as $pid => $rank) {
      if ($closeRank === null || abs($aimRank - $closeRank) > abs($rank - $aimRank)) {
        $closest = $rank;
        $closePid = $pid; 
      }
    }
    return $closePid;
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
      $orb = new Orb($game);
      $parities = $this->xml->xpath("reckon[@game='$game'] | outcome[@game='$game']");
      foreach($parities as $par){
        $key = isset($par['rival']) ? 'rival' : (isset($par['region']) ? 'region' : '');
        if(empty($key)){
          self::logMsg('Parity missing rival/region : ' . print_r($par, true));
          continue;
        }
        $id = $par[$key];
        if (!$filter){
          $include=TRUE;
        } elseif($positiveFilter){
          $include = in_array($id, $filter);
        } else {
          $include = ! in_array($id, $filter);
        }
        if($include){
          $orb->addNode(Node::par($id, $par['parity'], $par['rely'], $par['date']));
        }
      }      
      
      $ranks = $this->xml->xpath("rank[@game='$game']");
      foreach($ranks as $rank){
        $orb->addNode(Node::rank((string) $rank['id'], $this->id()));
      }
      
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
