<?php

require_once 'Page.php';
require_once 'Venue.php';
require_once 'VenuePage.php';


class LocatePage extends Page {


    const GEOPLACE_URL    = "https://maps.googleapis.com/maps/api/place/autocomplete/xml";
    const GEODETAILS_URL  = "https://maps.googleapis.com/maps/api/place/details/xml";
    const GEOTIMEZONE_URL = "https://maps.googleapis.com/maps/api/timezone/xml";
    const GEOCODE_URL     = "https://maps.googleapis.com/maps/api/geocode/xml";

    const GEOPLACE_API_KEY    = "AIzaSyDne6EhcdFtiEiUT-batwVilT9YFUAbYdM";
    const GEODETAILS_API_KEY  = "AIzaSyDne6EhcdFtiEiUT-batwVilT9YFUAbYdM";
    const GEOTIMEZONE_API_KEY = "AIzaSyBuYfjKqrIP463HtMMMx1QnCh2VoO7GB-Q";
    const GEOCODE_API_KEY     = "AIzaSyC4zcdOxikM54AHNQjcSLSd8d6N8Kebmfg";


    private $game;
    private $description;
    private $repost;
    private $hideAddressPrompt = 'hidden';
    

    public function __construct($template='locate'){
        parent::__construct($template);
        
        $this->game        = $this->req('game');
        $this->description = $this->req('venue');
        $this->repost      = $this->req('repost');
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
                $details = self::getDetails($placeid);
                if($details){
                    $vid = Venue::venueID(
                        $details['name'],
                        $details['locality'],
                        $details['admin1'],
                        $details['country_iso']
                    );
                    $venue = new Venue($vid, TRUE);
                    $venue->updateAtt('placeid', $placeid);
                    $this->furnish($venue, $details);
                }
            }
        }

        // Process a new venue submitted from LocatePage
        if(empty($vid)){
            $name     = $this->req('name');
            $locality = $this->req('locality');
            $admin1   = $this->req('admin1');
            $country  = $this->req('country');
            if(isset($name)
            && isset($locality)
            && isset($admin1)
            && isset($country)){
                $vid = Venue::venueID($name, $locality, $admin1, $country);
                if (!Venue::exists($vid)){
                    $venue = new Venue($vid, TRUE);
                    $description = "$name, $locality, $admin1, $country";
                    $placeid = self::getPlace($description);
                    if(isset($placeid)){
                        $this->furnish($venue, self::getDetails($placeid));
                    } else {
                        $tz = self::guessTimezone($locality, $admin1, $country);
                        $venue->updateAtt('tz', $tz);
                        $venue->save();
                    }
                }
            }
	}

	if (isset($vid)){    // repost the query with the located $vid
            $QWIK_URL = self::QWIK_URL;
            $this->req('vid', $vid);
            $query = http_build_query($this->req());
            $repost = $this->repost;
            $url = "$QWIK_URL/$repost?$query";
            header("Location: $url");
        }
    }


    private function furnish($venue, $address){
        if ($venue && $address){
            $venue->updateAtt('phone',   $address['phone']);
            $venue->updateAtt('url',     $address['url']);
            $venue->updateAtt('tz',      $address['tz']);
            $venue->updateAtt('lat',     $address['lat']);
            $venue->updateAtt('lng',     $address['lng']);
            $venue->updateAtt('address', $address['formatted']);
            $venue->updateAtt('str-num', $address['street_number']);
            $venue->updateAtt('route',   $address['route']);
            $venue->save();
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
        // resupply the prior entries if they could not be geocoded
        $name = $this->req('name');
        $locality = $this->req('locality');
        $admin1 = $this->req('admin1');
        $country = $this->req('country');
        $placeid = $this->req('placeid');
        $userCountry = $this->geolocate('countryCode');

        if (empty($name)){
            $name = $this->description;
            $geocoded = self::parseAddress($this->description, $userCountry);
            if($geocoded){
                $locality = $geocoded['locality'];
                $admin1   = $geocoded['admin1'];
                $country  = $geocoded['country_iso'];
                $placeid  = $geocoded['placeid'];
            }
        }

        $QWIK_URL = self::QWIK_URL;

        $vars = parent::variables();
        $vars['game']           = $this->game;
        $vars['homeURL']        = "$QWIK_URL/player.php";
	$vars['repost']         = $this->repost;
        $vars['venueName']      = isset($name)     ? $name     : '';
        $vars['venueLocality']  = isset($locality) ? $locality : '';
        $vars['venueAdmin1']    = isset($admin1)   ? $admin1   : '';
        $vars['venueCountry']   = isset($country)  ? $country  : $userCountry;
        $vars['datalists']      = $this->countryDataList();
        $vars['placeid']        = $placeid;
        
        return $vars;
    }


    static function geo($param, $key, $url){
        $result = NULL;
        $param['key'] = $key;
        $query = http_build_query($param);
        $contents = file_get_contents("$url?$query");
        $xml = new SimpleXMLElement($contents);
        $status = (string) $xml->status[0];
        if($status === 'OK'){
            $result = $xml;
        } else {
            $msg = $xml->error_message;
            self::logMsg("Google $status: $msg\n\t$url?$query");
        }
        return $result;
    }


    static function geoplace($text, $country){
        return self::geo(
            array('input'=>$text, 'components'=>"country:$country"),
            self::GEOPLACE_API_KEY,
            self::GEOPLACE_URL
        );
    }


    static function geodetails($placeid){
        $geo = self::geo(
            array('placeid'=>$placeid),
            self::GEODETAILS_API_KEY,
            self::GEODETAILS_URL
        );
        return isset($geo) ? $geo->result : NULL;
    }


    static function geotime($lat, $lng){
        $location = "$lat,$lng";
        return self::geo(
            array('location'=>$location, 'timestamp'=>time()),
            self::GEOTIMEZONE_API_KEY,
            self::GEOTIMEZONE_URL
        );
    }


    static function geocode($address, $country){
        $geo = self::geo(
            array('components'=>"$address|$country"),
            self::GEOCODE_API_KEY,
            self::GEOCODE_URL
        );
        return isset($geo) ? $geo->result : NULL;
    }


    static function getPlace($description, $country){
        $placeid = NULL;
        $xml = self::geoplace($description, $country);
        if(isset($xml)){
            $prediction = $xml->prediction[0];
            if(isset($prediction)){
                $placeid = (string) $prediction->place_id;
            }
        }
        return $placeid;
    }


    static function getDetails($placeid){
        $details = NULL;
        $result = self::geodetails($placeid);
        if(isset($result)){
            $details = array();
            $details['placeid'] = $placeid;

            $details['name'] = (string) $result->name[0];
            $details['formatted'] = (string) $result->formatted_address;

            $location = $result->xpath("//geometry/location")[0];
            $lat = (string) $location->lat;
            $lng = (string) $location->lng;
            $details['lat'] = $lat;
            $details['lng'] = $lng;
            $details['tz'] = self::getTimezone($lat, $lng);

            $addr = $result->xpath("address_component[type='country']");
            $details['country'] = isset($addr[0]) ? (string) $addr[0]->long_name : NULL;
            $details['country_iso'] = isset($addr[0]) ? (string) $addr[0]->short_name : NULL;

            $addr = $result->xpath("address_component[type='administrative_area_level_1']");
            $details['admin1'] = isset($addr[0]) ? (string) $addr[0]->long_name : NULL;
            $details['admin1_code'] = isset($addr[0]) ? (string) $addr[0]->short_name : NULL;

            $addr = $result->xpath("address_component[type='street_number']");
            $details['str-num'] = isset($addr[0]) ?  (string) $addr[0]->long_name : NULL;

            $addr = $result->xpath("address_component[type='route']");
            $details['route'] = isset($addr[0]) ? (string) $addr[0]->long_name : NULL;

            $addr = $result->xpath("address_component[type='locality']");
            $details['locality'] = isset($addr[0]) ? (string) $addr[0]->long_name : NULL;

            $details['phone'] = (string) $result->phone[0];
            $details['url'] = (string) $result->website[0];
        }
        return $details;
    }


    static function getTimezone($lat, $lng){
        $tz = '';
        $xml = self::geotime($lat, $lng);
        if(isset($xml)){
            $tz = (string) $xml->time_zone_id;
        }
        return $tz;
    }


    static function parseAddress($address, $country=NULL){
        $parsed = FALSE;
        $placeid = self::getPlace($address, $country);
        if (isset($placeid)){
            $parsed = self::getDetails($placeid);
        }
        return $parsed;
    }


    static function guessTimezone($location, $admin1, $country){
        $tz = NULL;
        $placeid = self::getPlace("$location, $admin1, $country");
        if(isset($placeid)){
            $detals = self::geodetails($placeid);
            if(isset($details)){
                $loc = $details->xpath("//geometry/location")[0];
                if(isset($loc)){
                    $lat = (string) $loc->lat;
                    $lng = (string) $loc->lng;
                    $tz = self::getTimezone($lat, $lng);
                }
            }
        }
        return $tz;
    }
    
}

?>
