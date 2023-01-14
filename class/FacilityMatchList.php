<?php

require_once 'Card.php';

/*******************************************************************************
    Class FacilityMatchList replicates a html snippet for each qwik record.
    The html snippet is embedded in a html template and located by a <div id=''>.
*******************************************************************************/

class FacilityMatchList extends Card {

    private $status = '';

    /*******************************************************************************
    Class facilityMatchList is constructed with a html template.

    $html String a html document containing a div to be replicated.
    $id   String a html div id to identify the html snippet to be replicated.
    *******************************************************************************/
    public function __construct($html, $status, $id=NULL){
        parent::__construct($html, $id);
 
        $this->status = $status;
    }   


    protected function loadUser($uid){
        return new Manager($uid);
    }


    public function replicate($html, $variables){
        $manager = $this->manager();
        if (is_null($manager)){
            self::logMsg("FacilityMatchList missing Manager");
            return '';
        }
        $venue = $manager->venue();
        $status = $this->status;
        $group = '';
        foreach($venue->matchStatus($status) as $matchXML) {
            $pid = $matchXML['pid'];
            $player = new Player($pid);
            $match = new Match($player, $matchXML);
            $matchVars = $match->vars($venue);
            $vars = $variables + $matchVars;
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }

}


?>
