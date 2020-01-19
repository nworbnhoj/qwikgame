<?php

require_once 'Options.php';

/*******************************************************************************
    Class SvidOptions constructs <option> elements for a Short Venue ID's
*******************************************************************************/

class SvidOptions extends Options {



    /*******************************************************************************
    Class Options 
    *******************************************************************************/
    public function __construct($game=NULL, $country=NULL){
        parent::__construct();
        $game = isset($game) ? $game : $this->req('game');
        $country = isset($country) ? $country : $this->req('country');
        parent::values(Qwik::svids($game));
    }


}


?>
