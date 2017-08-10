<?php

require_once 'Page.php';
require_once 'Venue.php';


class LocatePage extends Page {

    private $game;
    private $venueDesc;
    private $repost;
    

    public function __construct($template='locate'){
        parent::__construct($template);
        
        $this->game = $this->req('game');
        $this->venueDesc = $this->req('venue');
	    $this->repost = $this->req('repost');
    }


    public function serve(){
        if ($this->game == null){
            header("Location: ".self::QWIK_URL);
            return;
	    }
	    parent::serve();
	}
	
	
	public function processRequest(){

	    $vids = $this->matchShortVenueID($this->venueDesc, $this->game);
	    $matchCount = count($vids);
	    if($matchCount == 1){
	        $vid = $vids[0];
	    }

	    if($this->req('name') !== null
	    && $this->req('address') !== null
	    && $this->req('suburb') !== null
	    && $this->req('state') !== null
	    && $this->req('country') !== null){
	    	$vid = venueID(
	    		$this->req('name'), 
	    		$this->req('address'), 
            	$this->req('suburb'), 
            	$this->req('state'), 
            	$this->req('country')
		    );
            $venue = new Venue($vid, TRUE);
		}

	    if ($vid !== null){
            $this->req('vid', $vid);
            $query = http_build_query($this->req());
            $repost = $this->repost;
            header("location: ".self::QWIK_URL."/$repost?$query");
       }
    }
    
    
    
    
    /*******************************************************************************
    Returns an Array of Venue ID's (vid) that match the $svid provided.

    $svid    String    The Short Venue ID includes only the Name & Suburb of the Venue.

    The Short Venue ID $svid is a non-unique human convenient way of referring to a
    Venue. This functions finds zero or more $vid that match the $svid
    *******************************************************************************/
    function matchShortVenueID($svid, $game){
        $matchedVids = array();
        $vids = self::venues(strtolower($game));
        foreach($vids as $vid){
            if($svid === Venue::svid($vid)){
                $matchedVids[] = $vid;
            }
        }
        return $matchedVids;
    }


    


	public function variables(){

        $game = $this->game;
	    $venue = new Venue($this->venueDesc, TRUE);
	    $venueName = $venue->name();
	    $venueCountry = $venue->country();
	    $venueURL = $venue->url();
        $backLink = "<a href='".self::QWIK_URL;
        $backLink .= "/index.php?venue=$venueName&game=$game' target='_blank'><b>link</b></a>";

        $variables = parent::variables();

        $variables['vid']            = $venue->id();
        $variables['game']           = $this->game;
        $variables['homeURL']        = self::QWIK_URL."/player.php";
		$variables['repost']         = $this->repost;
        $variables['venueName']      = $venueName;
        $variables['venueAddress']   = $venue->address();
        $variables['venueSuburb']    = $venue->suburb();
        $variables['venueState']     = $venue->state();
        $variables['venueCountry']   = $venueCountry;
        $variables['countryOptions'] = $this->countryOptions($venueCountry, "\t\t\t\t\t");
        $variables['venuePhone']     = $venue->phone();
        $variables['venueURL']       = $venueURL;
        $variables['venueTZ']        = $venue->tz();
        $variables['venueLat']       = $venue->lat();
        $variables['venueLng']       = $venue->lng();
        $variables['venueNote']      = $venue->note();
//        $variables['venueRevertDiv'] = $venue->revertDiv();
        $variables['backLink']       = $backLink;
        $variables['venueUrlLink']   = "<a href='$venueURL'>{homepage}</a>";
        
        return $variables;
    }
    
}

?>
