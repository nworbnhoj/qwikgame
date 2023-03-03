<?php

require_once 'Card.php';

/*******************************************************************************
    Class AbilityList replicates a html snippet for each qwik record.
    The html snippet is embedded in a html template and located by a <div id=''>.
*******************************************************************************/

class AbilityList extends Card {


    /*******************************************************************************
    Class AbilityList is constructed with a html template.

    $html String a html document containing a div to be replicated.
    $id   String a html div id to identify the html snippet to be replicated.
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

        $group = '';
        $parityWord = array('{very_strong}', '{strong}', '{competent}', '{weak}', '{very_weak}');
        $playerVars = $player->playerVariables();
        $abilities = $player->reckonAbilities();
        foreach($abilities as $id => $ability){
            $reckonVars = array(
                'id'        => $id,
                'region'    => explode('|', $ability['region'])[0],
                'gameName'  => self::gameName($ability['game']),
                'parity'    => $parityWord[$ability['parity'] + 2]
            );
            $vars = $variables + $playerVars + $reckonVars;
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }

}


?>
