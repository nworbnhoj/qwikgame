<?php

require_once 'Card.php';
require_once 'Natch.php';

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


    protected function loadUser($uid){
        return new Player($uid);
    }


    public function replicate($html, $variables){
        $player = $this->player();
        if (is_null($player)){ return '';}

        $group="";
        $playerVars = $player->playerVariables();
        $friends = $player->reckonFriends();
        foreach($friends as $id => $friend){
            $reckonVars = array(
                'id'        => $id,
                'email'     => $friend['nick'],
                'gameName'  => self::gameName($friend['game']),
                'parity'    => Natch::parityStr($friend['parity']),
            );
            $vars = $variables + $playerVars + $reckonVars;
            $group .= $this->populate($html, $vars);
        }
        return $group;

    }

}


?>
