<?php

require_once 'Page.php';

class PlayerPage extends Page {

    private $game;
    private $venue;

    const SELECT_REGION = "
        <select name='region' class='region' required>
            <repeat id='reckon'>
                <option value='<v>region</v>'><v>region</v></option>
            </repeat>
        </select>
    ";


    public function __construct(){
        Page::__construct('player');

        $player = $this->player();
        if (is_null($player)){
            $this->logout();
        }

        $game = $this->req('game');

        $vid = $this->req('vid');
        if(isset($vid)){
            $venue = new Venue($vid);
        }

        if (isset($venue)){
            if($venue->addGame($game)){
                $venue->save();
                $this->logMsg("Added $game to $vid");
            }
        } elseif (!is_null($this->req('venue'))){
            if(is_null($this->req('repost'))){
                $this->req('repost', 'player.php');
            }
            $query = http_build_query($this->req());
            header("location: $qwikURL/locate.php?$query");
            return;
        }
    }


    public function processRequest(){
        $qwik = $this->req('qwik');
        $action = $this->req('action');
        $req = $this->req();
        $player = $this->player();
        switch ($qwik) {
            case "available":
                qwikAvailable($player, $req, $venue);
                break;
            case "keen":
                qwikKeen($player, $req, $venue);
                break;
            case 'accept':
                qwikAccept($player, $req);
                 break;
            case 'decline':
                qwikDecline($player, $req);
                 break;
            case 'familiar':
                qwikFamiliar($player, $req);
                break;
            case 'region':
                qwikRegion($player, $req);
                break;
            case "cancel":
                qwikCancel($player, $req);
                break;
            case "feedback":
                qwikFeedback($player, $req);
                break;
            case 'delete':
                qwikDelete($player, $req);
                break;
            case 'account':
                qwikAccount($player, $req);
                break;
            case 'msg':
                qwikMsg($player, $req);
                break;
            case 'login':
                $email = $page->req('email');
                if(isset($email)){
                    if($email != $player->email()){
                        $player->email($email);
                        $token = $player->token(Player::MINUTE);
                        $newID = $player->id();
                        $query = "qwik=login&pid=$newID&token=$token'";
                        header("Location: $qwikURL/player.php?$query");
                    }
                }
                break;
            case 'logout':
                logout();
                break;
            default:
    //             header("Location: error.php?msg=<b>Invalid post:<b> $qwik<br>");
        }

        $player->concludeMatches();
        $player->save();
    }


    public function variables(){

        $player = $this->player();
        $venue = $this->venue;
        $game = $this->game;

        $rnd = mt_rand(1,8);
        $message = null !== $player->email() ?
            "<t>Tip$rnd<    >" :
            'Please <b>activate</b> your account<br><br>An email has been sent with an activation link to click.';

        $familiarCheckboxes = $this->familiarCheckboxes($player);
        $playerNick = $player->nick();
        $historyCount = count($player->matchQuery("match[@status='history']"));

        $variables = Page::variables($player);

        $variables['vid']           = isset($venue) ? $venue->id() : '';
        $variables['venue']         = $this->req('venue');
        $variables['message']       = $message;
        $variables['playerName']    = is_null($playerNick) ? $player->email() : $playerNick;
        $variables['gameOptions']   = $this->gameOptions($game, "\t\t");
        $variables['familiarHidden']= empty($familiarCheckboxes) ? 'hidden' : ' ';
        $variables['hourRows']      = $this->hourRows();
        $variables['selectRegion']  = self::SELECT_REGION;
        $variables['regionOptions'] = $this->regionOptions($player, "\t\t\t");
        $variables['historyHidden'] = $historyCount == 0 ? 'hidden' : '';
//            'historyForms'   => $historyForms;
        $variables['reputation']    = $player->repWord();
        $variables['reputationLink']= "<a href='info.php#reputation'>reputation</a>";
        $variables['thumbs']        = $player->repThumbs();
        $variables['playerNick']    = $playerNick;
        $variables['playerURL']     = $player->url();
        $variables['playerEmail']   = $player->email();
        $variables['datalists']     = $this->datalists();
        $variables['MAP_ICON']      = MAP_ICON;
        $variables['SEND_ICON']     = SEND_ICON;

        return $variables;
    }



///// QWIK SWITCH ///////////////////////////////////////////////////////////



function qwikAvailable($player, $request, $venue){
    if(isset($request['game']) & isset($request['vid']) & isset($request['parity'])){
        $newID = $player->availableAdd(
            $request['game'],
            $request['vid'],
            $request['parity'],
            $venue->tz(),
            isset($request['smtwtfs']) ? $request['smtwtfs'] : FALSE,
            $request
        );
        $venue->addPlayer($player->id());
        return $newID;
    }
}


function qwikKeen($player, $req, $venue){
//echo "<br>QWIKKEEN<br>";
    if(empty($req)
    || empty($venue)
    || empty($req['game'])
    || (empty($req['today']) && empty($req['tomorrow']))){
        logMsg("qwikKeen() missing required arguments");
        return;
    }

    $game = $req['game'];

    // build an array of Familiar Rivals to invite
    $pid = $this->id();
    $familiarRids = array();
    if (isset($req['invite'])){
        $emails = $req['invite'];
        if (is_array($emails)){
            foreach($emails as $email){
                $familiarRids[] = anonID($email);
            }
        }
    }
    unset($familiarRids[$pid]);    // exclude self;


    // build an array of other available Rivals to invite
    $anonRids = array();
    if ($req['invite-available']){
        $anonRids = array_diff(
            $venue->playerIDs(),
            $familiarRids        // exclude explicit invitations
        );
    }
    unset($anonRids[$pid]);    // exclude self;


    $days = array('today','tomorrow');
    foreach($days as $day){
        $date = $venue->dateTime($day);
        $hours = (int) $req[$day];
        if ($hours > 0){
             $match = $player->matchKeen($game, $venue, $date, $hours);
             $match->invite($familiarRids, TRUE);
             $match->invite($anonRids);
             $match->save();
        }
    }
}


function qwikAccept($player, $request){
    if(isset($request['id']) & isset($request['hour'])){
        $matchID = $request['id'];
        $match = $player->matchID($matchID);
        if (!isset($match)){
            header("Location: error.php?msg=unable to locate match.");
            return;
        }
        $match->accept($request['hour']);
        $player->save();
    }
}



function qwikDecline($player, $request){
//echo "<br>QWIKDCLINE<br>";
    $playerID = $player->id();
    if(isset($request['id'])){
        $player->matchDecline($request['id']);
    }
}

function qwikFamiliar($player, $request){
    if(isset($request['game'])
    && isset($request['rival'])
    && isset($request['parity'])){
        $player->familiar($request['game'], $request['rival'], $request['parity'], $log);
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

        if ($rival->exists()){
            $rival->updateRep($request['rep']);
            updateCongCert($player, $request['id'], $rival);
            $rival->save();
        }
    } else {
        header("Location: error.php?msg=malformed feedback.");
    }
}




function qwikDelete($player, $request){
    $player->delete($request['id']);
}



function qwikAccount($player, $request){
//echo "<br>QWIKACCOUNT<br>";
    global $qwikURL, $DAY;
    if(isset($request['nick'])){
        $player->nick($request['nick']);
    }

    if(isset($request['url'])){
        $player->url($request['url']);
    }

    if(isset($request['email'])){
        $email = $request['email'];
        if ($email != $player->email()){
            emailChange($email, $player->id(), $player->token(Player::DAY));
        }
    }

    if(isset($request['lang'])){
        $player->lang($request['lang']);
    }

    if(isset($request['account']) && ($request['account'] === 'quit')) {
        $player->emailQuit();
        $player->quit();
        logout();

        header("Location: $qwikURL");
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
        $hourRows .= "$tabs\t<th>$day<    h>\n";
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
            $hourRows .= "$tabs\t<td class='toggle' bit='$bit' $hidden>$hr12<    d>\n";
            $bit = $bit * 2;
        }
        $hourRows .= "$tabs<    r>\n";
    }
    return $hourRows;
}


function familiarCheckboxes($player){
    $checkboxes = '';
    $reckons = $player->reckon("email");
    foreach($reckons as $reckon){
        $email = $reckon['email'];
        $rid = $reckon['rival'];
        $checkboxes .= "
            <span class='nowrap'>
                <input type='checkbox' name='invite[]' value='$email'>
                $email
            </span>";
    }
    return $checkboxes;
}





function regionOptions($player, $tabs){
    $regions = regions($player);
    $options = '';
    foreach($regions as $region){
           $options .= "$tabs<option value='$region'>$region</option>\n";
    }
    return $options;
}


}

?>
