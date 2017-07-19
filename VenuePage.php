<?php

require 'Page.php';

class VenuePage extends Page {

    private $venue;

    public function __construct(){
        parent::__construct('venue');

	    $vid = $this->req('vid');
        $this->venue = new Venue($vid, $this->log());
    }


    public function serve($template=null){
        if (!$this->venue->exists()){
            header("Location: ".QWIK_URL);
            return;
	    }
	    parent::serve($template);
	}


	public function processRequest(){
        if (!$this->venue->exists()){
            return;
        }

        if($this->player() !== null
	    && $this->req('name') !== null
        && $this->req('address') !== null
        && $this->req('suburb') !== null
        && $this->req('state') !== null
        && $this->req('country') !== null){
	        $venue->update($this->req());
	    }

        $this->venue->concludeReverts();
    }



    public function variables(){
        $game = $this->req('game');
        $venueName = $this->venue->name();
        $venueUrl = $this->venue->url();
        $venueState = (empty($this->venue->state())) ? geolocate('region') : $this->venue->state();
        $venueCountry = $this->venue->country();
        $backLink = "<a href='".QWIK_URL;
        $backlink .= "/index.php?venue=$venueName&game=$game' target='_blank'><b>link</b></a>";

        $variables = parent::variables();
        
        $variables['vid']           = $this->venue->id();
        $variables['playerCount']   = $this->venue->playerCount();
        $variables['message']       = '';
        $variables['displayHidden'] = '';
        $variables['editHidden']    = 'hidden';
        $variables['repostInputs']  = repostIns($repost, "\t\t\t");
        $variables['venueName']     = $venueName;
        $variables['venueAddress']  = $this->venue->address();
        $variables['venueSuburb']   = $this->venue->suburb();
        $variables['venueState']    = $venueState;
        $variables['venueCountry']  = $venueCountry;
        $variables['countryOptions']= countryOptions($venueCountry, "\t\t\t\t\t");
        $variables['venuePhone']    = $this->venue->phone();
        $variables['venueURL']      = $this->venue->url();
        $variables['venueTZ']       = $this->venue->tz();
        $variables['venueLat']      = $this->venue->lat();
        $variables['venueLng']      = $this->venue->lng();
        $variables['venueNote']     = $this->venue->note();
        $variables['venueRevertDiv']= $this->venue->revertDiv();
        $variables['backLink']      = $backLink;
        $variables['venueUrlLink']  = "<a href='$venueUrl'><t>homepage</t></a>";
        
	    return $variables;
	}

}

?>
