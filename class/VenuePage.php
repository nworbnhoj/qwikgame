<?php

require_once 'Page.php';

class VenuePage extends Page {

    const GEOPLACE_URL   = "https://maps.googleapis.com/maps/api/place/autocomplete/xml";
    const GEODETAILS_URL = "https://maps.googleapis.com/maps/api/place/details/xml";
    const GEOCODE_URL    = "https://maps.googleapis.com/maps/api/geocode/xml";

    const GEOPLACE_API_KEY   = "AIzaSyDne6EhcdFtiEiUT-batwVilT9YFUAbYdM";
    const GEODETAILS_API_KEY = "AIzaSyDne6EhcdFtiEiUT-batwVilT9YFUAbYdM";
    const GEOCODE_API_KEY    = "AIzaSyC4zcdOxikM54AHNQjcSLSd8d6N8Kebmfg";

    const PDR_XPATH_STATUS       = "/PlaceDetailsResponse/status/text()";
    const PDR_XPATH_ERROR        = "/PlaceDetailsResponse/error_message/text()";
    const PDR_XPATH_FORMATTED    = "/PlaceDetailsResponse/result/formatted_address/text()";
    const PDR_XPATH_COUNTRY      = "/PlaceDetailsResponse/result/address_component[type='country']/long_name/text()";
    const PDR_XPATH_COUNTRY_CODE = "/PlaceDetailsResponse/result/address_component[type='country']/short_name/text()";
    const PDR_XPATH_ADMIN1       = "/PlaceDetailsResponse/result/address_component[type='administrative_area_level_1']/long_name/text()";
    const PDR_XPATH_ADMIN1_CODE  = "/PlaceDetailsResponse/result/address_component[type='administrative_area_level_1']/short_name/text()";
    const PDR_XPATH_ADMIN2       = "/PlaceDetailsResponse/result/address_component[type='administrative_area_level_2']/long_name/text()";
    const PDR_XPATH_ADMIN3       = "/PlaceDetailsResponse/result/address_component[type='administrative_area_level_3']/long_name/text()";
    const PDR_XPATH_LOCALITY     = "/PlaceDetailsResponse/result/address_component[type='locality']/long_name/text()";
    const PDR_XPATH_PHONE        = "/PlaceDetailsResponse/result/address_component[type='phone']/text()";
    const PDR_XPATH_URL          = "/PlaceDetailsResponse/result/address_component[type='url']/text()";
    const PDR_XPATH_LAT          = "/PlaceDetailsResponse/result/geometry/location/lat/text()";
    const PDR_XPATH_LNG          = "/PlaceDetailsResponse/result/geometry/location/lng/text()";

    const ACR_XPATH_STATUS     = "/AutocompletionResponse/status/text()";
    const ACR_XPATH_ERROR      = "/AutocompletionResponse/error_message/text()";
    const ACR_XPATH_PREDICTION = "/AutocompletionResponse/prediction";


    private $venue;

    public function __construct($template='venue'){
        parent::__construct($template);
        $vid = $this->req('vid');
        $this->venue = new Venue($vid);
    }


    public function serve(){
        if (!$this->venue->ok()){
            header("Location: ".self::QWIK_URL);
            return;
        }
        parent::serve();
    }


    public function processRequest(){
        if (!$this->venue->ok()){
            return;
        }

        $venue = $this->venue;
        $req = $this->req;
        if($this->player() !== null
        && $req('name') !== null
        && $req('address') !== null
        && $req('country') !== null){
            $address = self::parseAddress($req['address'].', '.$req['country']);

            $save = $venue->updateAtt('name',     $req['name']);
            $save = $venue->updateAtt('locality', $address['locality'])  || $save;
            $save = $venue->updateAtt('admin1',   $address['admin1'])    || $save;
            $save = $venue->updateAtt('country',  $address['country'])   || $save;
            if($save){
                $venue->updateID();
            }
            $save = $venue->updateAtt('phone',     $req['phone'])         || $save;
            $save = $venue->updateAtt('url',       $req['url'])           || $save;
            $save = $venue->updateAtt('tz',        $req['tz'])            || $save;
            $save = $venue->updateAtt('note',      $req['note'])          || $save;
            $save = $venue->updateAtt('lat',       $address['lat'])       || $save;
            $save = $venue->updateAtt('lng',       $address['lng'])       || $save;
            $save = $venue->updateAtt('placeid',   $address['placeid'])   || $save;
            $save = $venue->updateAtt('address',   $address['formatted']) || $save;
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


    static function geo($param, $key, $url){
        $result = null;
        $param['key'] = $key;
        $query = http_build_query($param);
        $contents = file_get_contents("$url?$query");
        $xml = new SimpleXMLElement($contents);
        $status = (string) $xml->status[0];
        if($status === 'OK'){
            $result = $xml->result;
        } else {
            $msg = $xml->error_message;
            self::logMsg("Google $status: $msg\n\t$url?$query");
        }
        return $result;
    }




    static function geoplace($text){
        return self::geo(
            array('input'=>$text),
            self::GEOPLACE_API_KEY,
            self::GEOPLACE_URL
        );
    }


    static function geodetails($placeid){
        return self::geo(
            array('placeid'=>$placeid),
            self::GEODETAILS_API_KEY,
            self::GEODETAILS_URL
        );
    }


    static function geocode($address, $country){
        return self::geo(
            array('components'=>"$address|$country"),
            self::GEOCODE_API_KEY,
            self::GEOCODE_URL
        );
    }


    static function getPlace($description){
        $placeid = NULL;
        $xml = self::geoplace($description);
        if(isset($xml)){
            $prediction = $xml->prediction[0];
            if(isset($prediction)){
                $placeid = (string) $prediction->place_id;
            }
        }
        return $placeid;
    }



    static function getDetails($placeid){
        $details = array();
        $xml = self::geodetails($placeid);
        if(isset($xml)){
            $details['placeid'] = $placeid;

            $result = $xml->result;
            $details['formatted'] = (string) $result->formatted_address;

            $location = $result->geometry->location;
            $details['lat'] = (string) $location->lat;
            $details['lng'] = (string) $location->lng;

            $addr = $result->xpath("address_component[type='country']")[0];
            $details['country'] = (string) $addr->long_name;
            $details['country_code'] = (string) $addr->short_name;

            $addr = $result->xpath("address_component[type='administrative_area_level_1']")[0];
            $details['admin1'] = (string) $addr->long_name;
            $details['admin1_code'] = (string) $addr->short_name;

            $addr = $result->xpath("address_component[type='administrative_area_level_2']")[0];
            $details['admin2'] = (string) $addr->long_name;

            $addr = $result->xpath("address_component[type='administrative_area_level_3']")[0];
            $details['admin3'] = (string) $addr->long_name;

            $addr = $result->xpath("address_component[type='locality']")[0];
            $details['locality'] = (string) $addr->long_name;

            $details['phone'] = (string) $result->phone[0];            $details['url'] = (string) $result->website[0];
        }
        return $details;
    }



    static function parseAddress($address){
        $parsed = FALSE;
        $placeid = self::getPlace($address);
        if (isset($placeid)){
            $parsed = getDetails($placeid);
        }
        return $parsed;
    }


}

?>
