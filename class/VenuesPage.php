<?php

require_once 'Page.php';
require_once 'Locate.php';

class VenuesPage extends Page {

    public function __construct($templateName='venues'){
        parent::__construct(NULL, $templateName);

    }


    public function variables(){
        $vars = parent::variables();
        $loc = Locate::geolocate('location');
        $game = $this->req('game');

        $vars['game']        = $game;
        $vars['lat']         = isset($loc['lat']) ? $loc['lat'] : NULL ;
        $vars['lng']         = isset($loc['lng']) ? $loc['lng'] : NULL ;
        $vars['gameOptions'] = $this->gameOptions($game, "\t\t");
        return $vars;
    }

}

?>
