<?php

require_once 'Base.php';

/*******************************************************************************
    Class SimilarVenues replicates a html snippet post element.
    The html snippet is embedded in a html template and located by a <div id=''>.
*******************************************************************************/

class SimilarVenues extends Base {

    private $venueDescription;
    /*******************************************************************************
    Class SimilarVenues is constructed with a html template.

    $html String a html document containing a div to be replicated.
    $id   String a html div id to identify the html snippet to be identified.
    *******************************************************************************/
    public function __construct($html, $id, $venueDescription){
        parent::__construct($html, $id);
        $this->venueDescription = $venueDescription;
    }


    public function replicate($html){
        $html = parent::replicate($html); // removes 'base' class
        $group = '';
        $similar = array_slice($this->similarVenues($this->venueDescription), 0, 5);
        foreach($similar as $vid){
            try {
                $venue = new Venue($vid);
                $vars = array(
                    'vid'    => $vid,
                    'name'   => implode(', ',explode('|',$vid)),
                    'players'=> $venue->playerCount(),
                );
                $group .= $this->populate($html, $vars);
            } catch (RuntimeException $e){
                self::logThrown($e);
            }
        }
        return $group;
    }

}


?>
