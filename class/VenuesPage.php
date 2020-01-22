<?php

require_once 'Page.php';
require_once 'Locate.php';

class VenuesPage extends Page {

    public function __construct($templateName='venues'){
        parent::__construct(Html::readTemplate($templateName), $templateName);

    }


    public function variables(){
        $vars = parent::variables();
        $loc = Locate::geolocate('location');

        $vars['game'] = $this->req('game');
        $vars['lat']  = $loc['lat'];
        $vars['lng']  = $loc['lng'];
        return $vars;
    }

}

?>
