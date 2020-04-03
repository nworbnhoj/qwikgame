<?php

require_once 'Card.php';

/*******************************************************************************
    Class MatchList replicates a html snippet for each qwik record.
    The html snippet is embedded in a html template and located by a <div id=''>.
*******************************************************************************/

class MatchList extends Card {

    private $status = '';

    /*******************************************************************************
    Class MatchList is constructed with a html template.

    $html String a html document containing a div to be replicated.
    $id   String a html div id to identify the html snippet to be replicated.
    *******************************************************************************/
    public function __construct($html, $status, $id=NULL){
        parent::__construct($html, $id);
 
        $this->status = $status;
    }


    public function replicate($html){
        $player = $this->player();
        if (is_null($player)){ return '';}

        $html = parent::replicate($html); // removes 'base' class
        $status = $this->status;
        $group = '';
        $playerVars = $this->playerVariables($player);
        foreach($player->matchStatus($status) as $matchXML) {
            $match = new Match($player, $matchXML);
            $matchVars = $match->variables();
            $vars = $playerVars + $matchVars + self::$icons;
            $vars['venueLink'] = $this->venueLink($match->vid());
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }

}


?>
