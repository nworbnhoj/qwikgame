<?php

require_once 'Card.php';

/*******************************************************************************
    Class FriendList replicates a html snippet for each qwik record.
    The html snippet is embedded in a html template and located by a <div id=''>.
*******************************************************************************/

class FriendList extends Card {


    /*******************************************************************************
    Class FriendList is constructed with a html template.

    $html String a html document containing a div to be replicated.
    $id   String a html div id to identify the html snippet to be identified.
    *******************************************************************************/
    public function __construct($html, $id=NULL){
        parent::__construct($html, $id);
    }


    public function replicate($html, $variables){
        $player = $this->player();
        if (is_null($player)){ return '';}

        $group="";
        $playerVars = $this->playerVariables($player);
        $reckoning = $player->reckon("rival");
        foreach($reckoning as $reckon){
            $email = (string) $reckon['email'];
            $rid = (string) $reckon['rival'];
            $rival = new Player($rid);
            if (isset($rival) && $rival->ok()){
                $nick = $rival->nick();
            }
            if(empty($nick)){
               $nick = empty($email) ? Qwik::snip($rid) : $email;
            }
            $parity = intval($reckon['parity']);
            $game = (string) $reckon['game'];
            $reckonVars = array(
                'id'        => $reckon['id'][0],
                'email'     => $nick,
                'gameName'  => self::gameName($game),
                'parity'    => self::parityStr($parity)
            );
            $vars = $variables + $playerVars + $reckonVars;
            $group .= $this->populate($html, $vars);
        }
        return $group;

    }

}


?>
