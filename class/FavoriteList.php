<?php

require_once 'Card.php';

/*******************************************************************************
    Class FavoriteList replicates a html snippet for each qwik record.
    The html snippet is embedded in a html template and located by a <div id=''>.
*******************************************************************************/

class FavoriteList extends Card {


    /*******************************************************************************
    Class FavoriteList is constructed with a html template.

    $html String a html document containing a div to be replicated.
    $id   String a html div id to identify the html snippet to be replicated.
    *******************************************************************************/
    public function __construct($html, $id=NULL){
        parent::__construct($html, $id);
    }


    public function replicate($html, $variables){
        $player = $this->player();
        if (is_null($player)){ return '';}

        $group = ''; 
        $playerVars = $player->playerVariables();
        $available = $player->available();
        foreach($available as $avail){
            $game = (string) $avail['game'];
            $availVars = array(
                'id'        => (string) $avail['id'],
                'gameName'  => self::gameName($game),
                'parity'    => (string) $avail['parity'],
                'weekSpan'  => self::weekSpan($avail),
                'venueLink' => $this->venueLink($avail->venue, $game),
                'game'      => $game
            );
            $vars = $variables + $playerVars + $availVars;
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }

}


?>
