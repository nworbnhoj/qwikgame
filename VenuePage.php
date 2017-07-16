<?php

require 'Page.php';

class VenuePage extends Page {

    private $venue;

    public function __construct(){
        Page::__construct();

	    $vid = $this->req('vid');
        $this->venue = new Venue($vid, $this->log());
    }


    public function serve($template='venue'){
        if (!$this->venue->exists()){
            header("Location: ".QWIK_URL);
            return;
	    }
	    Page::serve($template);
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

        $variables = array(
            'vid'			=> $this->venue->id(),
            'game'			=> $game,
            'homeURL'		=> QWIK_URL,
            'playerCount'	=> $this->venue->playerCount(),
            'message'		=> '',
            'displayHidden'	=> '',
            'editHidden'	=> 'hidden',
            'repostInputs'	=> repostIns($repost, "\t\t\t"),
            'venueName'		=> $venueName,
            'venueAddress'  => $this->venue->address(),
            'venueSuburb'	=> $this->venue->suburb(),
            'venueState' 	=> $venueState,
            'venueCountry'  => $venueCountry,
            'countryOptions'=> countryOptions($venueCountry, "\t\t\t\t\t"),
            'venuePhone'	=> $this->venue->phone(),
            'venueURL'		=> $this->venue->url(),
            'venueTZ'		=> $this->venue->tz(),
            'venueLat'		=> $this->venue->lat(),
            'venueLng'		=> $this->venue->lng(),
            'venueNote'		=> $this->venue->note(),
            'venueRevertDiv'=> $this->venue->revertDiv(),
            'backLink'		=> $backLink,
            'venueUrlLink'	=> "<a href='$venueUrl'><t>homepage</t></a>",
            'INFO_ICON'		=> INFO_ICON,
            'HOME_ICON'     => HOME_ICON,
            'TWITTER_ICON'	=> TWITTER_ICON,
            'EMAIL_ICON'	=> EMAIL_ICON,
            'FACEBOOK_ICON'	=> FACEBOOK_ICON,
        );
	    return $variables;
	}

}

?>
