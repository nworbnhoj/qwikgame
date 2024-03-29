<?php

require_once 'Page.php';
require_once 'Venue.php';
require_once 'MatchList.php';
require_once 'FriendCheckboxes.php';
require_once 'Locate.php';
require_once 'Options.php';


class MatchPage extends Page {

    private $game;
    private $venue;

    public function __construct($templateName='match'){
        parent::__construct(NULL, $templateName);

        $player = $this->player();
        if (is_null($player)
        || !$player->ok()){
            $req = $this->req();
            self::logMsg("MatchPage missing player ".json_encode($req));
            $this->logout();
            return;
        }

        $this->game = $this->req('game');

        $vid = $this->req('vid');
        $placeid = $this->req('placeid');
        if(isset($vid)){
            if (Venue::exists($vid)){
                try {
                    $this->venue = new Venue($vid);
                } catch (RuntimeException $e){
                    self::alert("{Oops}");
                    self::logThrown($e);
                    unset($vid);
                }
            } elseif (isset($placeid)) {
                $details = Locate::getDetails($placeid);  
                if($details){  // the $vid provided is actually a valid google placeId
                    $vid = Venue::venueID(
                        $details['name'],
                        $details['locality'],
                        empty($details['admin1_code']) ? $details['admin1'] : $details['admin1_code'],
                        $details['country_iso']
                    );
                    try {
                        $this->venue = new Venue($vid, TRUE);
                        if($this->venue->ok()){
                            $this->req('vid', $vid);
                            $this->venue->placeid($placeid);
                            $this->venue->furnish($details);
                        } else {
                          self::alert("Sorry - failed to create new Venue");
                        }
                    } catch (RuntimeException $e){
                        self::alert("{Oops}");
                        self::logThrown($e);
                        unset($vid);
                    }
                }
            }
        }

        if (isset($this->venue)){
            $venue = $this->venue;
            $game = $this->game;
            $games = $venue->games();
            if(!in_array($game, $games)){
                $open = $venue->openHours();
                if(empty($open)){
                    $open['Sun'] = Hours::HRS_24;
                    $open['Mon'] = Hours::HRS_24;
                    $open['Tue'] = Hours::HRS_24;
                    $open['Wed'] = Hours::HRS_24;
                    $open['Thu'] = Hours::HRS_24;
                    $open['Fri'] = Hours::HRS_24;
                    $open['Sat'] = Hours::HRS_24;
                }
                $venue->facilitySet($game, $open);
                $venue->save(TRUE);
                self::logMsg("Added ".$game." to $vid");
            }
        }
    }


    protected function loadUser($uid){
        return new Player($uid);
    }


    public function processRequest(){
        $result = parent::processRequest();
        if(!is_null($result)){ return $result; }   // request handled by parent
        
        $player = $this->player();
        $qwik = $this->req('qwik');
        $req = $this->req();
        switch ($qwik) {
            case "keen":
                $result = $this->qwikKeen($player, $req, $this->venue);
                break;
            case 'accept':
                $result = $this->qwikAccept($player, $req);
                 break;
            case 'decline':
                $result = $this->qwikDecline($player, $req);
                 break;
            case "cancel":
                $result = $this->qwikCancel($player, $req);
                break;
            case "feedback":
                $result = $this->qwikFeedback($player, $req);
                break;
            case 'delete':
                $result = $this->qwikDelete($player, $req);
                break;
            case 'msg':
                $result = $this->qwikMsg($player, $req);
                break;
            default:
                $result =  NULL;
        }

        $player->concludeMatches();
        $player->save();
        return $result;
    }


    public function variables(){
        $vars = parent::variables();

        $vars['MAP_ICON']      = self::MAP_ICON;
        $vars['SEND_ICON']     = self::SEND_ICON;

        $venue = $this->venue;
        if (!is_null($venue)){
            $vars['vid'] = $venue->id();
            $vars['venue'] = $venue->name();
        } else {
            $vars['vid'] = '';
            $vars['venue'] = '';
        }

        $player = $this->player();
        if (!is_null($player)){
            $playerNick = $player->nick();
            $playerEmail = $player->email();
            $playerName = empty($playerNick) ? $playerEmail : $playerNick;
            $historyCount = count($player->matchQuery("match[@status='history']"));
            $regionOptions = new Options($player->regions(), Options::VALUE_TEMPLATE);

            $vars['checkboxFriendsDisplay'] = $player->hasFriends() ? 'block' : 'none';
            $vars['historyHidden'] = $historyCount == 0 ? 'hidden' : '';
            $vars['regionOptions'] = $regionOptions->make();
            $vars['reputation']    = $player->repWord();
            $vars['thumbs']        = $player->repThumbs();
            $vars['playerNick']    = $playerNick;
            $vars['playerURL']     = $player->url();
            $vars['playerEmail']   = $playerEmail;
            $vars['LOGOUT_ICON']   = self::LOGOUT_ICON;
        }

        $vars['hourRows'] = self::hourRows(Page::TWODAYS);
        $vars['gameOptions']   = $this->gameOptions($this->game, "\t\t");
        
        $loc = Locate::geolocate(array('latitude', 'longitude'));
        $vars['lat'] = isset($loc) && isset($loc['latitude']) ? $loc['latitude'] : NULL ;
        $vars['lng'] = isset($loc) && isset($loc['longitude']) ? $loc['longitude'] : NULL ;

        return $vars;
    }


    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();

        $keenList = new MatchList($html, 'keen', 'keen.match');
        $vars['keenMatches'] = $keenList->make();

        $invitationList = new MatchList($html, 'invitation', 'invitation.match');
        $vars['invitationMatches'] = $invitationList->make();

        $acceptedList = new MatchList($html, 'accepted', 'accepted.match');
        $vars['acceptedMatches'] = $acceptedList->make();

        $confirmedList = new MatchList($html, 'confirmed', 'confirmed.match');
        $vars['confirmedMatches'] = $confirmedList->make();

        $feedbackList = new MatchList($html, 'feedback', 'feedback.match');
        $vars['feedbackMatches'] = $feedbackList->make();

        $cancelledList = new MatchList($html, 'cancelled', 'cancelled.match');
        $vars['cancelledMatches'] = $cancelledList->make();

        $friendCheckboxes = new FriendCheckboxes($html, 'friendEmail');
        $vars['friendEmails'] = $friendCheckboxes->make();

        $historyList = new MatchList($html, 'history', 'history.match');
        $vars['historyMatches'] = $historyList->make();
	
        return parent::make($vars); 
    }



///// QWIK SWITCH ///////////////////////////////////////////////////////////


function qwikKeen($player, $req, $venue){
    if(empty($req)
    || empty($venue)
    || empty($req['game'])
    || (empty($req['today']) && empty($req['tomorrow']))){
        self::logMsg("qwikKeen() missing required arguments: post " . print_r($req, true));
        return;
    }

    $mid = null;
    $game = $req['game'];

    $rids = array();
    if (isset($req['beckon'])){   // add anon Rivals $rid=>NULL
        foreach($venue->playerIDs() as $rid){
            $rids[$rid] = NULL;
        }
    }

    $emails = isset($req['invite']) ? $req['invite'] : NULL;
    if (is_array($emails)){    // add friendly Rivals $rid=>$email
        foreach($emails as $email){
            $id = Player::anonID($email);
            if(isset($id)){
                $rids[$id] = $email;
            }
        }
    }
    unset($rids[$player->id()]);         // exclude self;

    $days = array('today','tomorrow');
    foreach($days as $day){
        $datetime = $venue->dateTime($day);
        $hours = new Hours((int) $req[$day]);
        //$hours &= $venue->facilityHours($game, $datetime);
        if (!$hours->none()){
           $date = $datetime->format('d-m-Y');
           $match = $player->matchKeen($game, $venue, $date, $hours, $rids);
           $mid = $match->id();
        }
    }
    $player->save();
    return $mid;  // caution - return the last mid
}



function qwikAccept($player, $req){
    $mid = $req['id'];
    $hour = $req['hour'];
    if(isset($mid) && isset($hour)){
        return $player->matchAccept($mid, $hour);
    } else {
        return NULL;
    }
}



function qwikDecline($player, $request){
    $playerID = $player->id();
    if(isset($request['id'])){
      $match = $player->matchDecline($request['id']);
      $mid = $match->id();
    } else {
      $mid = null;
    }
    return $mid;
}


function qwikCancel($player, $req){
  $id = $req['id'];
  if($id){
    $player->matchCancel($id);
    return $id;
  }
  return NULL;
}


function qwikFeedback($player, $request){
    if(isset($request['id']) & isset($request['rep']) & isset($request['parity'])){
        $rival = $player->outcomeAdd(
            $request['id'],
            $request['parity'],
            $request['rep']
        );

        if ($rival->ok()){
            $rival->updateRep($request['rep']);
            $this->updateCongCert($player, $request['id'], $rival);
            $rival->save();
        }
    } else {
        self::logMsg("malformed feedback");
    }
    return $request['id'];
}


function qwikDelete($player, $request){
    $player->deleteData($request['id']);
    return $request['id'];
}



function qwikMsg($player, $req){
    if(isset($req['id']) & isset($req['msg'])){
        $player->matchMsg($req['id'], $req['msg']);
    }
}



    /*******************************************************************************
    qwikgame attempts to estimate the PARITY of possible RIVALs prior to each MATCH.

    After each MATCH both PLAYERSs rate the PARITY of their RIVAL's ability:
        +2  much stronger
        +1  stronger
         0  well matched
        -1  weaker
        -2  much weaker
    There may be DISPARITY between the two ratings. 

    A player's RELYability measures their consistency in rating their RIVALs.
    There can be DISPARITY between two Players rating of each other. DISPARITY causes
    a Players RELYability to drop, but the PLayer with the lower historical RELYability
    will suffer most from any DISPARITY (on the assumption that they are the probable
    cause)

    The CONFIDENCE in each PARITY rating is used to resolve DISPARITY during estimates.
    Each PARITY rating has a CONFIDENCE which is high when two rivals with 
    high CONGRUENCE rate each other with no DISPARITY (and vice versa).


    // refine CONGRUENCE and CERTAINTY when RIVAL Feedback also exists
    ********************************************************************************/

    function updateCongCert($player, $matchID, $rival){
        $pOutcome = $player->outcome($matchID);
        $rOutcome = $rival->outcome($matchID);
        if (NULL !== $pOutcome && NULL !== $rOutcome){

            $pParity = intval($pOutcome['parity']);
            $rParity = intval($rOutcome['parity']);
            $disparity = abs($rParity + $pParity);    // note '+' sign & range [0,4]

            $pRely = $player->rely();
            $rRely = $rival->rely();
            $totalRely = $pRely + $rRely;

            if ($totalRely > 0){
                $player->rely($disparity * $pRely / $totalRely);
                $rival->rely($disparity * $rRely / $totalRely);
            }

            $congruence = 4 - $disparity;             // range [0,4]
            $player->outcome($matchID, ($pRely * $congruence) / 4);
            $rival->outcome($matchID, ($rRely * $congruence) / 4);
        }
    }
}

?>
