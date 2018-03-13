<?php

require_once 'Page.php';
require_once 'Venue.php';
require_once 'VenuePage.php';


class LocatePage extends Page {

    private $game;
    private $description;
    private $repost;
    

    public function __construct($template='locate'){
        parent::__construct($template);
        
        $this->game = $this->req('game');
        $this->description = $this->req('venue');
        $this->repost = $this->req('repost');
    }


    public function serve(){
        if (empty($this->game)){
            header("Location: ".self::QWIK_URL);
            return;
        }
	parent::serve();
    }
	
	
    public function processRequest(){
        $vid = NULL;
        $description = $this->description;

        // first check if the description is a vid (venue id)
        if (Venue::exists($description)){
            $vid = $description;
        }

        // Process a svid (short vid) submitted in PlayerPage
        if(empty($vid)){    // check if the description is a svid
            $vids = $this->matchShortVenueID($description, $this->game);
	    $matchCount = count($vids);
	    $vid = ($matchCount == 1) ? $vids[0] : null;
        }

        // Process a new venue submitted from LocatePage
        if(empty($vid)
        && $this->req('name') !== null
        && $this->req('address') !== null
        && $this->req('country') !== null){    // make a new venue from the request
            $reqName = $this->req('name');
            $reqAddress = $this->req('address');
            $reqCountry = $this->req('country');

            $address = VenuePage::parseAddress("$reqName, $reqAddress, $reqCountry");
	    $vid = Venue::venueID(
                $this->req('name'),
                $address['locality'],
                $address['admin1'],
                $this->req('country')
            );
            $venue = new Venue($vid, TRUE);
	}

	if ($vid !== null){    // repost the query with the located $vid
            $this->req('vid', $vid);
            $query = http_build_query($this->req());
            $repost = $this->repost;
            header("location: ".self::QWIK_URL."/$repost?$query");
       }
    }
    
    
    
    
    /*******************************************************************************
    Returns an Array of Venue ID's (vid) that match the $svid provided.

    $svid  String 	 The Short Venue ID includes only the Name & Locality of the Venue.

    The Short Venue ID $svid is a non-unique human convenient way of referring to a
    Venue. This functions finds zero or more $vid that match the $svid
    *******************************************************************************/
    function matchShortVenueID($svid, $game){
        $matchedVids = array();
        $vids = self::venues($game);
        foreach($vids as $vid){
            if($svid === Venue::svid($vid)){
                $matchedVids[] = $vid;
            }
        }
        return $matchedVids;
    }


    


    public function variables(){
        $address = VenuePage::parseAddress($this->description);
        $country = $address['country'];

        $QWIK_URL = self::QWIK_URL;

        $variables = parent::variables();
        $variables['game']           = $this->game;
        $variables['homeURL']        = "$QWIK_URL/player.php";
	$variables['repost']         = $this->repost;
        $variables['venueName']      = $this->description;
        $variables['venueAddress']   = $address['formatted'];
        $variables['countryOptions'] = $this->countryOptions($country, "\t\t\t\t\t");
        
        return $variables;
    }
    
}

?>
