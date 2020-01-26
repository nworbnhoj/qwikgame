<?php

require_once 'Listing.php';

/*******************************************************************************
    Class AbilityListing replicates a html snippet for each qwik record.
    The html snippet is embedded in a html template and located by a <div id=''>.
*******************************************************************************/

class AbilityListing extends Listing {


    /*******************************************************************************
    Class AbilityListing is constructed with a html template.

    $html String a html document containing a div to be replicated.
    $id   String a html div id to identify the html snippet to be replicated.
    *******************************************************************************/
    public function __construct($html){
        parent::__construct($html);
    }


    public function replicate($html){
        $html = parent::replicate($html); // removes 'base' class
        $group = '';
        $player = $this->player();
        $abilities = array('{very_weak}', '{weak}', '{competent}', '{strong}', '{very_strong}');
        $playerVars = $this->playerVariables($player);
        $reckoning = $player->reckon("region");
        foreach($reckoning as $reckon){
            $game = $reckon['game'];
            $ability = $reckon['ability'];
            $reckonVars = array(
                'id'        => $reckon['id'],
                'region'    => explode(',', $reckon['region'])[0],
                'game'      => self::qwikGames()["$game"],
                'ability'   => $abilities["$ability"]
            );
            $vars = $playerVars + $reckonVars + self::$icons;
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }

}


?>