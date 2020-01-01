<?php

require_once 'Listing.php';

/*******************************************************************************
    Class MatchListing replicates a html snippet for each qwik record.
    The html snippet is embedded in a html template and located by a <div id=''>.
*******************************************************************************/

class MatchListing extends Listing {

    private $status = array();

    /*******************************************************************************
    Class AbilityListing is constructed with a html template.

    $html String a html document containing a div to be replicated.
    $id   String a html div id to identify the html snippet to be replicated.
    *******************************************************************************/
    public function __construct($html=NULL, $id='match', $status=''){
        parent::__construct($html, $id);

        // if a status was not provided to the constructor then get the ststus from
        // the (json) request (see *.match.listing.json.php). 
        $this->status = empty($status) ? $this->req('status') : $status;
    }


    public function replicate($html){
        $html = parent::replicate($html); // removes 'base' class
        $player = $this->player();
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
