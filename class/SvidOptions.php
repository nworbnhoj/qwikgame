<?php

require_once 'Options.php';

/*******************************************************************************
    Class SvidOptions constructs <option> elements for a Short Venue ID's
*******************************************************************************/

class SvidOptions extends Options {


    public function __construct($game=NULL, $country=NULL){
        parent::__construct();
        parent::values(Qwik::svids($game, $country));
    }


}


?>
