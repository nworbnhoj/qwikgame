<?php

require 'Page.php';

class VenuePage extends Page {

    private $venue;

    public function __construct($template='venue'){
        parent::__construct($template);

	    $vid = $this->req('vid');
        $this->venue = new Venue($vid);
    }


    public function serve(){
        if (!$this->venue->exists()){
            header("Location: ".self::QWIK_URL);
            return;
	    }
	    parent::serve();
	}


	public function processRequest(){
        if (!$this->venue->exists()){
            return;
        }

        $venue = $this->venue;
        $req = $this->req;
        if($this->player() !== null
        && $req('name') !== null
        && $req('address') !== null
        && $req('country') !== null){
            $address = self::parseAddress($req['address'], $req['country']);

            $save = $venue->updateAtt('name', $req['name']);
            $save = $venue->updateAtt('address', $address['formatted']) || $save;
            $save = $venue->updateAtt('locality', $address['locality']) || $save;
            $save = $venue->updateAtt('admin1', $address['admin1']) || $save;
            $save = $venue->updateAtt('country', $address['country']) || $save;
            if($save){
                $venue->updateID();
            }
            $save = $venue->updateAtt('phone', $req['phone']) || $save;
            $save = $venue->updateAtt('url', $req['url'])  || $save;
            $save = $venue->updateAtt('tz', $req['tz']) || $save;
            $save = $venue->updateAtt('note', $req['note']) || $save;
            $save = $venue->updateAtt('lat', $address['lat']) || $save;
            $save = $venue->updateAtt('lng', $address['lng']) || $save;
            if($save){
                $venue->save();
            }
        }

        $venue->concludeReverts();
    }



    public function variables(){
        $game = $this->req('game');
        $venueName = $this->venue->name();
        $venueUrl = $this->venue->url();
        $venueCountry = $this->venue->country();
        $backLink = "<a href='".self::QWIK_URL;
        $backLink .= "/index.php?venue=$venueName&game=$game' target='_blank'><b>link</b></a>";

	$qwikGames = $this->qwikGames();
        $venueGames = "";
        foreach($this->venue->games() as $gameKey){
            $gameName = $qwikGames[$gameKey];
            $venueGames .= "{$gameName} ";
        }

        $vars = parent::variables();
        
        $vars['vid']           = $this->venue->id();
        $vars['playerCount']   = $this->venue->playerCount();
        $vars['message']       = '';
        $vars['displayHidden'] = '';
        $vars['editHidden']    = 'hidden';
        $vars['venueName']     = $venueName;
        $vars['venueAddress']  = $this->venue->address();
        $vars['venueCountry']  = $venueCountry;
        $vars['countryOptions']= $this->countryOptions($venueCountry, "\t\t\t\t\t");
        $vars['venuePhone']    = $this->venue->phone();
        $vars['venueURL']      = $this->venue->url();
        $vars['venueTZ']       = $this->venue->tz();
        $vars['venueLat']      = $this->venue->lat();
        $vars['venueLng']      = $this->venue->lng();
        $vars['venueNote']     = $this->venue->note();
        $vars['venueRevertDiv']= $this->venue->revertDiv();
        $vars['backLink']      = $backLink;
        $vars['venueUrlLink']  = "<a href='$venueUrl'>{homepage}</a>";
        $vars['games']         = $venueGames;
        
        return $vars;
    }


    const GEOCODE_API_KEY = "AIzaSyC4zcdOxikM54AHNQjcSLSd8d6N8Kebmfg";
    const GEOCODE_URL = "https://maps.googleapis.com/maps/api/geocode/xml";

    static function geocode($address, $country){
        $param = array();
	$param['components'] = "$address|$country";
        $param['key'] = self::GEOCODE_API_KEY;
        $query = http_build_query($param);
        $GEOCODE_URL = self::GEOCODE_URL;
        $url = "$GEOCODE_URL?$query";
        return new SimpleXMLElement(file_get_contents($url));
    }


    const GEO_XPATH_STATUS       = "/GeocodeResponse/status/text()";
    const GEO_XPATH_COUNTRY      = "/GeocodeResponse/result/address_component[type='country']/long_name/text()";
    const GEO_XPATH_COUNTRY_CODE = "/GeocodeResponse/result/address_component[type='country']/short_name/text()";
    const GEO_XPATH_ADMIN1       = "/GeocodeResponse/result/address_component[type='administrative_area_level_1']/long_name/text()";
    const GEO_XPATH_ADMIN2       = "/GeocodeResponse/result/address_component[type='administrative_area_level_2']/long_name/text()";
    const GEO_XPATH_ADMIN3       = "/GeocodeResponse/result/address_component[type='administrative_area_level_3']/long_name/text()";
    const GEO_XPATH_LOCALITY     = "/GeocodeResponse/result/address_component[type='locality']/long_name/text()";
    const GEO_XPATH_FORMATTED    = "/GeocodeResponse/result/formatted_address/text()";
    const GEO_XPATH_LAT          = "/GeocodeResponse/result/geometry/location/lat/text()";
    const GEO_XPATH_LNG          = "/GeocodeResponse/result/geometry/location/lng/text()";


    static function parseAddress($address, $country){
        $parsed = array();
        $xml = self::geocode($address, $country);
        $status = $xml->xpath(self::GEO_XPATH_STATUS);
        switch ($status){
            case "OK":
                $parsed['country']      = $xml->xpath(self::GEO_XPATH_COUNTRY);
                $parsed['country_code'] = $xml->xpath(self::GEO_XPATH_COUNTRY_CODE);
                $parsed['admin1']       = $xml->xpath(self::GEO_XPATH_ADMIN1);
                $parsed['admin2']       = $xml->xpath(self::GEO_XPATH_ADMIN2);
                $parsed['admin3']       = $xml->xpath(self::GEO_XPATH_ADMIN3);
                $parsed['locality']     = $xml->xpath(self::GEO_XPATH_LOCALITY);
                $parsed['formatted']    = $xml->xpath(self::GEO_XPATH_FORMATTED);
                $parsed['lat']          = $xml->xpath(self::GEO_XPATH_LAT);
                $parsed['lng']          = $xml->xpath(self::GEO_XPATH_LNG);
                break;

            case "ZERO_RESULTS":
            case "OVER_QUERY_LIMIT":
            case "REQUEST_DENIED":
            case "INVALID_REQUEST":
            case "UNKNOWN_ERROR":
            default:
                self::logMsg("Geocode $status: $address | $country");
        }
        return $parsed;
    }

}

?>
