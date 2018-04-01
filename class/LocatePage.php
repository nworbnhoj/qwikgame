<?php

require_once 'Page.php';
require_once 'Venue.php';
require_once 'VenuePage.php';


class LocatePage extends Page {

    private $game;
    private $description;
    private $repost;
    private $hideAddressPrompt = 'hidden';
    

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
	    $vid = ($matchCount == 1) ? $vids[0] : NULL;
        }

        // Process a new venue from a placeid from LocatePage
        if(empty($vid)){
            $placeid = $this->req('placeid');
            if(isset($placeid)){
                $this->newVenue($placeid, $this->req('name'));
            }
        }

        // Process a new venue submitted from LocatePage
        if(empty($vid)){
            $name = $this->req('name');
            $locality = $this->req('locality');
            $admin1 = $this->req('admin1');
            $country = $this->req('country');
            if(isset($name)
            && isset($locality)
            && isset($admin1)
            && isset($country)){
                $description = "$name, $locality, $admin1, $country";
                $placeid = VenuePage::getPlace($description);
                if(isset($address)){
                    $vid = $this->newVenue($placeid, $name);
                } else {
                    $vid = Venue::venueID($name, $locality, $admin1, $country);
                    $venue = new Venue($vid, TRUE);
                }
            }
	}

	if ($vid !== null){    // repost the query with the located $vid
            $this->req('vid', $vid);
            $query = http_build_query($this->req());
            $repost = $this->repost;
            header("location: ".self::QWIK_URL."/$repost?$query");
        }
    }
    
    
    private function newVenue($placeid, $reqName){
        $vid = NULL;
        $address = VenuePage::getDetails($placeid);

        if($address){
            $vid = Venue::venueID(
                $reqName,
                $address['locality'],
                $address['admin1'],
                $address['country_code'];
            );
            $venue = new Venue($vid, TRUE);

            $venue->updateAtt('phone',   $address['phone']);
            $venue->updateAtt('url',     $address['url']);
            $venue->updateAtt('tz',      $address['tz']);
            $venue->updateAtt('lat',     $address['lat']);
            $venue->updateAtt('lng',     $address['lng']);
            $venue->updateAtt('placeid', $address['placeid']);
            $venue->updateAtt('address', $address['formatted']);
            $venue->save();
        }
        return $vid;
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
        // resupply the prior entries if they could not be geocoded
        $name = $this->req('name');
        $locality = $this->req('locality');
        $admin1 = $this->req('admin1');
        $country = $this->req('country');

        if (empty($name)){
            $name = $this->description;
            $geocoded = VenuePage::parseAddress($this->description);
            if($geocoded){
                $locality = $geocoded['locality'];
                $admin1 = $geocoded['admin1'];
                $country = $geocoded['country'];
            }
        }

        $QWIK_URL = self::QWIK_URL;

        $variables = parent::variables();
        $variables['game']           = $this->game;
        $variables['homeURL']        = "$QWIK_URL/player.php";
	$variables['repost']         = $this->repost;
        $variables['venueName']      = $name;
        $variables['venueLocality']  = $locality;
        $variables['venueAdmin1']    = $admin1;
        $variables['venueCountry']   = isset($country) ? $country : $this->geolocate('countryCode') ;
        $variables['countryOptions'] = $this->countryOptions($country, "\t\t\t\t\t");
        $variables['datalists']      = $this->countryDataList();
        
        return $variables;
    }
    
}

?>
