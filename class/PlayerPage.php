<?php

require_once 'Page.php';

class PlayerPage extends Page {

    const SELECT_PARITY = 
        "<select name='parity'>
            <option value='2'>{much_stronger}</option>
            <option value='1'>{stronger}</option>
            <option value='0' selected>{well_matched}</option>
            <option value='-1'>{weaker}</option>
            <option value='-2'>{much_weaker}</option>
        </select>";
    const BUTTON_THUMB = "<button type='button' id='rep-thumb'  class='" . self::THUMB_UP_ICON . "'></button>";
    const LINK_REP = "<a href='info.php#reputation'>{Reputation}</a>";

    private $game;
    private $venue;

    public function __construct($template='player'){
        parent::__construct($template);

        $player = $this->player();
        if (is_null($player)
        || !$player->ok()){
            $this->logout();
            return;
        }

        $this->game = $this->req('game');

        $vid = $this->req('vid');
        if(isset($vid)){
            $this->venue = new Venue($vid);
        }

        if (isset($this->venue)){
            if($this->venue->addGame($this->game)){
                $this->venue->save();
                self::logMsg("Added ".$this->game." to $vid");
            }
        } elseif (!is_null($this->req('venue'))){
            if(is_null($this->req('repost'))){
                $this->req('repost', 'player.php');
            }
            $query = http_build_query($this->req());
            header("Location: ".self::QWIK_URL."/locate.php?$query");
            return;
        }
    }


    public function processRequest(){
        $player = $this->player();
        $qwik = $this->req('qwik');
        $req = $this->req();
        $result = null;
        switch ($qwik) {
            case "available":
                $result = $this->qwikAvailable($player, $this->venue);
                break;
            case "keen":
                $result = $this->qwikKeen($player, $req, $this->venue);
                break;
            case 'accept':
                $result = $this->qwikAccept($player, $req);
                 break;
            case 'decline':
                $result = $this->qwikDecline($player, $req);
                 break;
            case 'familiar':
                $result = $this->qwikFamiliar($player, $req);
                break;
            case 'region':
                $result = $this->qwikRegion($player, $req);
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
            case 'account':
                $result = $this->qwikAccount($player, $req);
                break;
            case 'msg':
                $result = $this->qwikMsg($player, $req);
                break;
            case 'login':
                $email = $this->req('email');
                if(isset($email)
                && $email != $player->email()){
                    $player->email($email);
                    if (!headers_sent()){
                        $url = $player->authURL(Player::MINUTE);
                        header("Location: $url");
                    }
                }
                break;
            case 'logout':
                $result = $this->logout();
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

        $vars['hourRows']      = $this->hourRows();
        $vars['datalists']     = $this->datalists();
        $vars['MAP_ICON']      = self::MAP_ICON;
        $vars['SEND_ICON']     = self::SEND_ICON;
        $vars['alert-hidden']  = 'hidden';

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
	    $reckons = $player->reckon("email");
            $historyCount = count($player->matchQuery("match[@status='history']"));

            $vars['message']       = "{Welcome} <b>$playerName</b>";
            $vars['familiarHidden']= empty($reckons) ? 'hidden' : ' ';
            $vars['regionOptions'] = $this->regionOptions($player, "\t\t\t");
            $vars['historyHidden'] = $historyCount == 0 ? 'hidden' : '';
            $vars['reputation']    = $player->repWord();
            $vars['reputationLink']= self::LINK_REP;
            $vars['thumbs']        = $player->repThumbs();
            $vars['playerNick']    = $playerNick;
            $vars['playerURL']     = $player->url();
            $vars['playerEmail']   = $playerEmail;
            $vars['LOGOUT_ICON']   = self::LOGOUT_ICON;
            $vars['paritySelect']  = self::SELECT_PARITY;
            $vars['thumbButton']   = self::BUTTON_THUMB;

            // special case: new un-activated player
            if (is_null($playerEmail)){
                $vars['message']    = '{Please activate...}';
                $vars['playerName'] = ' ';
                $vars['playerEmail'] = ' ';
            }
        }

        $game = $this->game;
        if (!is_null($game)){
            $vars['gameOptions']   = $this->gameOptions($game, "\t\t");
        }

        return $vars;
    }



///// QWIK SWITCH ///////////////////////////////////////////////////////////



    function qwikAvailable($player, $venue){
        if($this->req('game')
        & $this->req('parity')
        & $this->req('vid')){
            $newID = $player->availableAdd(
                $this->req('game'),
                $this->req('vid'),
                $this->req('parity'),
                $venue->tz(),
                $this->req('smtwtfs') ? $this->req('smtwtfs') : FALSE,
                $this->req()
            );
            if(is_null($venue)){
                $pid = $player->id();
                $vid = $this->req('vid');
                $this->logMsg("Unable to add player to venue:\tpid=$pid\t vid=$vid");
            } else {
                $venue->addPlayer($player->id());
                $venue->save();
            }
            return $newID;
        }
        return NULL;
    }



function qwikKeen($player, $req, $venue){
    if(empty($req)
    || empty($venue)
    || empty($req['game'])
    || (empty($req['today']) && empty($req['tomorrow']))){
        self::logMsg("qwikKeen() missing required arguments");
        return;
    }

    $game = $req['game'];

    $rids = array();
    if (isset($req['beckon'])){   // add anon Rivals $rid=>NULL
        foreach($venue->playerIDs() as $rid){
            $rids[$rid] = NULL;
        }
    }

    $emails = $req['invite'];
    if (is_array($emails)){    // add Familiar Rivals $rid=>$email
        foreach($emails as $email){
            $rids[Player::anonID($email)] = $email;
        }
    }
    unset($rids[$player->id()]);         // exclude self;

    $days = array('today','tomorrow');
    foreach($days as $day){
        $date = $venue->dateTime($day);
        $hours = new Hours((int) $req[$day]);
        if (!$hours->none()){
             $player->matchKeen($game, $venue, $date, $hours, $rids);
        }
    }
    $player->save();
}



function qwikAccept($player, $request){
    if(isset($request['id']) & isset($request['hour'])){
        $matchID = $request['id'];
        $match = $player->matchID($matchID);
        if (!isset($match)){
            self::logMsg("unable to locate match: $matchID");
            return;
        }
        $match->accept(new Hours($request['hour']));
        $player->save();
    }
}



function qwikDecline($player, $request){
    $playerID = $player->id();
    if(isset($request['id'])){
        $player->matchDecline($request['id']);
    }
}

function qwikFamiliar($player, $request){
    if(isset($request['game'])
    && isset($request['rival'])
    && isset($request['parity'])){
        $player->familiar($request['game'], $request['rival'], $request['parity']);
    }
}


function qwikRegion($player, $request){
    if(isset($request['game'])
        && isset($request['ability'])
        && isset($request['region'])){
            $player->region($request['game'], $request['ability'], $request['region']);
    }
}



function qwikCancel($player, $req){
    if(isset($req['id'])){
        $player->matchCancel($req['id']);
    }
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
}




function qwikDelete($player, $request){
    $player->deleteData($request['id']);
}



function qwikAccount($player, $request){
    if(isset($request['nick'])){
        $player->nick($request['nick']);
    }

    if(isset($request['url'])){
        $player->url($request['url']);
    }

    if(isset($request['email'])){
        $email = $request['email'];
        if ($email != $player->email()){
            $player->emailChange($email);
        }
    }

    if(isset($request['lang'])){
        $player->lang($request['lang']);
    }

    if(isset($request['account']) && ($request['account'] === 'quit')) {
        $player->emailQuit();
        $player->quit();
        $this->logout();

        header("Location: ".self::QWIK_URL);
    }
}



function qwikMsg($player, $req){
    if(isset($req['id']) & isset($req['msg'])){
        $player->matchMsg($req['id'], $req['msg']);
    }
}



function hourRows(){
    $hourRows = '';
    $days = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');
    $tabs = "\t\t\t\t";
    foreach($days as $day){
        $bit = 1;
        $hourRows .= "$tabs<tr>\n";
        $hourRows .= "$tabs\t<input name='$day' type='hidden' value='0'>\n";
        $hourRows .= "$tabs\t<th>$day</th>\n";
        for($hr24=0; $hr24<=23; $hr24++){
            if (($hr24 < 6) | ($hr24 > 20)){
                $hidden = 'hidden';
            } else {
                $hidden = '';
            }
            if ($hr24 <= 12){
                $hr12 = $hr24;
            } else {
                $hr12 = $hr24-12;
            }
            $hourRows .= "$tabs\t<td class='toggle' bit='$bit' $hidden>$hr12</td>\n";
            $bit = $bit * 2;
        }
        $hourRows .= "$tabs</tr>\n";
    }
    return $hourRows;
}


    function regionOptions($player, $tabs){
        $regions = $this->regions($player);
        $options = '';
        foreach($regions as $region){
               $options .= "$tabs<option value='$region'>$region</option>\n";
        }
        return $options;
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

            $player->rely($disparity * $pRely / $totalRely);
            $rival->rely($disparity * $rRely / $totalRely);

            $congruence = 4 - $disparity;             // range [0,4]
            $player->outcome($matchID, ($pRely * $congruence) / 4);
            $rival->outcome($matchID, ($rRely * $congruence) / 4);
        }
    }
}

?>
