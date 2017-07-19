<?php

require 'Page.php';


class LocatePage extends Page {

    private $game;
    private $venueDesc;
    private $repost;
    

    public function __construct(){
        parent::__construct('locate');
        
        $this->game = $this->req('game');
        $this->venueDesc = $this->req('venue');
	    $this->repost = $this->req('repost');
    }


    public function serve($template=null){
        if (!$this->game != null){
            header("Location: ".QWIK_URL);
            return;
	    }
	    parent::serve($template);
	}
	
	
	public function processRequest(){

	    $vids = matchShortVenueID($this->venueDesc, $this->game);
	    $matchCount = count($vids);
	    if($matchCount == 1){
	    	$this->req('vid', $vids[0]);
	    	$query = http_build_query($this->req());
	    	$repost = $this->repost;
            header("location: ".QWIK_URL."/$repost?$query");
		    return;
	    }

	    if($this->req('name') != null
	    && $this->req('address') != null
	    && $this->req('suburb') != null
	    && $this->req('state') != null
	    && $this->req('country') != null){
	    	$vid = venueID(
	    		$this->req('name'), 
	    		$this->req('address'), 
            	$this->req('suburb'), 
            	$this->req('state'), 
            	$this->req('country')
		    );
	    	$xml = "<venue id='$vid'><game>$this->game</game></venue>";
	    	$venue = new simplexmlelement($xml, LIBXML_NOENT);
		    updateVenue($venue, $this->req());

            $this->req('vid', $vid);
            $query = http_build_query($this->req());
	    	$repost = $this->repost;
            header("location: ".QWIK_URL."/$repost?$query");
            return;
	    }
    }


	public function variables(){

        $game = $this->game;
	    $venue = new Venue($this->venueDesc, $this->log(), TRUE);
	    $venueName = $venue->name();
	    $venueCountry = $venue->country();
	    $venueURL = $venue->url();
        $backLink = "<a href='".QWIK_URL;
        $backLink .= "/index.php?venue=$venueName&game=$game' target='_blank'><b>link</b></a>";

        $variables = parent::variables();

        $variables['vid']            = $venue->id();
        $variables['game']           = $this->game;
        $variables['homeURL']        = QWIK_URL."/player.php";
		$variables['repost']         = $this->repost;
        $variables['venueName']      = $venueName;
        $variables['venueAddress']   = $venue->address();
        $variables['venueSuburb']    = $venue->suburb();
        $variables['venueState']     = $venue->state();
        $variables['venueCountry']   = $venueCountry;
        $variables['countryOptions'] = countryOptions($venueCountry, "\t\t\t\t\t");
        $variables['venuePhone']     = $venue->phone();
        $variables['venueURL']       = $venueURL;
        $variables['venueTZ']        = $venue->tz();
        $variables['venueLat']       = $venue->lat();
        $variables['venueLng']       = $venue->lng();
        $variables['venueNote']      = $venue->note();
//        $variables['venueRevertDiv'] = $venue->revertDiv();
        $variables['backLink']       = $backLink;
        $variables['venueUrlLink']   = "<a href='$venueURL'><t>homepage</t></a>";
        
        return $variables;
    }
    
}

?>
