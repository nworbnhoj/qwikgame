<?php

require_once 'Page.php';
require_once 'Geo.php';

class VenuesPage extends Page {

    public function __construct($template='venues'){
        parent::__construct($template);
    }


    public function variables(){
        $vars = parent::variables();
        $loc = Geo::geolocate('location');

        $vars['game'] = $this->req('game');
        $vars['lat']  = $loc['lat'];
        $vars['lng']  = $loc['lng'];
        return $vars;
    }

}

?>
