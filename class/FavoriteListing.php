<?php

require_once 'Base.php';

/*******************************************************************************
    Class FavoriteListing replicates a html snippet for each qwik record.
    The html snippet is embedded in a html template and located by a <div id=''>.
*******************************************************************************/

class FavoriteListing extends Base {


    /*******************************************************************************
    Class FavoriteListing is constructed with a html template.

    $html String a html document containing a div to be replicated.
    $id   String a html div id to identify the html snippet to be replicated.
    *******************************************************************************/
    public function __construct($html){
        parent::__construct($html);
    }


    public function replicate($html){
        $player = $this->player();
        if (is_null($player)){ return '';}

        $html = parent::replicate($html); // removes 'base' class       
        $group = ''; 
        $playerVars = $this->playerVariables($player);
        $available = $player->available();
        foreach($available as $avail){
            $game = (string) $avail['game'];
            $availVars = array(
                'id'        => (string) $avail['id'],
                'gameName'  => self::gameName($game),
                'parity'    => (string) $avail['parity'],
                'weekSpan'  => $this->weekSpan($avail),
                'venueLink' => $this->venueLink($avail->venue)
            );
            $vars = $playerVars + $availVars + self::$icons;
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }


    private function weekSpan($xml){
        $html = "";
        $hrs = $xml->xpath("hrs");
        foreach($hrs as $hr){
            $hours = new Hours($hr);
            $html .= self::daySpan($hours->roster(), $hr['day']);
        }
        return $html;
    }

}


?>
