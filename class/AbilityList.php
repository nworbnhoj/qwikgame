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
    public function __construct($html, $id){
        parent::__construct($html, $id);
    }


    public function replicate($html){
        $player = $this->player();
        if (is_null($player)){ return '';}

        $html = parent::replicate($html); // removes 'base' class
        $group = '';
        $abilities = array('{very_weak}', '{weak}', '{competent}', '{strong}', '{very_strong}');
        $playerVars = $this->playerVariables($player);
        $reckoning = $player->reckon("region");
        foreach($reckoning as $reckon){
            $game = (string) $reckon['game'];
            $ability = $reckon['ability'];
            $reckonVars = array(
                'id'        => $reckon['id'],
                'region'    => explode(',', $reckon['region'])[0],
                'gameName'  => self::gameName($game),
                'ability'   => $abilities["$ability"]
            );
            $vars = $playerVars + $reckonVars + self::$icons;
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }

}


?>
