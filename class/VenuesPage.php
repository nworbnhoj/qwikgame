<?php

require_once 'Page.php';
require_once 'Locate.php';

class VenuesPage extends Page {

    public function __construct($templateName='venues'){
        parent::__construct(NULL, $templateName);

    }


    public function variables(){
        $vars = parent::variables();
        $loc = Locate::geolocate(array('latitude', 'longitude'));
        $game = $this->req('game');

        $vars['game']        = $game;
        $vars['lat']         = isset($loc['latitude']) ? $loc['latitude'] : NULL ;
        $vars['lng']         = isset($loc['longitude']) ? $loc['longitude'] : NULL ;
        $vars['gameOptions'] = $this->gameOptions($game, "\t\t");
        return $vars;
    }

}

?>
