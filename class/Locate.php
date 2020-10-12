<?php

require_once 'Qwik.php';
require_once 'Defend.php';
require_once 'Service.php';


class Locate extends Qwik {
    
    static $geoplace;
    static $geodetails;    
    static $geotimezone;    
    static $geocode;

    // https://stackoverflow.com/questions/693691/how-to-initialize-static-variables
    static function initStatic(){
        self::$geoplace = new Service("geoplace");
        self::$geodetails = new Service("geodetails");
        self::$geotimezone = new Service("geotimezone");
        self::$geocode = new Service("geocode");
    }


    static function geo($param, $key, $url){
        $result = NULL;
        $param['key'] = $key;
        try{
            $query = http_build_query($param);
            $xml = Defend::xml("$url?$query");
            $status = (string) $xml->status[0];
            if($status === 'OK' || $status === 'ZERO_RESULTS'){
                $result = $xml;
            } else {
                throw new RuntimeException($status);
            }
        } catch (RuntimeException $e){
            $msg = $e->getMessage();
            self::logMsg("Google geocoding: $msg\n$url?$query\n$result");
        }
        return $result;
    }


    static function geoplace($text, $country){
        return self::geo(
            array('input'=>$text, 'components'=>"country:$country"),
            self::$geoplace->key('private'),
            self::$geoplace->url("xml")
        );
    }


    static function geodetails($placeid){
        $geo = self::geo(
            array('placeid'=>$placeid),
            self::$geodetails->key('private'),
            self::$geodetails->url("xml")
        );
        return isset($geo) ? $geo->result : NULL;
    }


    static function geotime($lat, $lng){
        $location = "$lat,$lng";
        return self::geo(
            array('location'=>$location, 'timestamp'=>time()),
            self::$geotimezone->key('private'),
            self::$geotimezone->url("xml")
        );
    }


    static function geocode($address, $country){
        $geo = self::geo(
            array('components'=>"$address|$country"),
            self::$geocode->key('private'),
            self::$geocode->url("xml")
        );
        return isset($geo) ? $geo->result : NULL;
    }


    static function revgeocode($lat, $lng){
      $type = "country|administrative_area_level_1|locality";
      $geo = self::geo(
        array('latlng'=>"$lat,$lng", 'result_type'=>$type),
        self::$geocode->key('private'),
        self::$geocode->url("xml")
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
            $details['country'] = isset($addr[0]) ? (string) $addr[0]->long_name : "";
            $details['country_iso'] = isset($addr[0]) ? (string) $addr[0]->short_name : "";

            $addr = $result->xpath("address_component[type='administrative_area_level_1']");
            $details['admin1'] = isset($addr[0]) ? (string) $addr[0]->long_name : "";
            $details['admin1_code'] = isset($addr[0]) ? (string) $addr[0]->short_name : "";

            $addr = $result->xpath("address_component[type='street_number']");
            $details['street_number'] = isset($addr[0]) ?  (string) $addr[0]->long_name : "";

            $addr = $result->xpath("address_component[type='route']");
            $details['route'] = isset($addr[0]) ? (string) $addr[0]->long_name : "";

            $addr = $result->xpath("address_component[type='locality']");
            $details['locality'] = isset($addr[0]) ? (string) $addr[0]->long_name : "";

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
        $placeid = self::getPlace("$location, $admin1", $country);
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
    

    static function geolocate($key){
        global $geo;
        if(!isset($geo)){
            $geo = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip='.$_SERVER['REMOTE_ADDR']));
            if(!isset($geo)){ return NULL; }   // geoplugin.net is offline
        }
        if(is_array($key)){
            $result = array();
            foreach($key as $k){
                $geoKey = "geoplugin_$k";
                $result[$k] = isset($geo[$geoKey]) ? $geo[$geoKey] : NULL ;
            }
            return $result;
        } else {
            $geoKey = "geoplugin_$key";
            return isset($geo[$geoKey]) ? $geo[$geoKey] : NULL ;
        }
    }


    public function __construct(){
        parent::__construct();
    }


    static function geoGuess($input){
        $result = "{}";
    	$param = array();
        $param['input'] = $input;
        $param['key'] = self::$geoplace->key('private');
        $url = self::$geoplace->url("json");
        try{
            $query = http_build_query($param);
            $json = Defend::json("$url?$query");
            $decoded = json_decode($json, TRUE);
            $status = (string) $decoded["status"];
            if($status === 'OK' || $status === 'ZERO_RESULTS'){
                $result = $json;
            } else {
                throw new RuntimeException($status);
            }
        } catch (RuntimeException $e){
            $msg = $e->getMessage();
            self::logMsg("Google geocoding: $msg\n$url?$query\n$msg");
        }
        return $result;
    }


  static function getAddress($lat, $lng){
    $address = [];
    $xml = self::revgeocode($lat, $lng);
    $components = $xml->address_component;
    if(isset($components)){
      foreach($components as $component){
        foreach($component->type as $type){
          switch ((string) $type){
            case 'country':
              $address['country']  = (string) $component->short_name;
              break ;
            case 'administrative_area_level_1':
              $name = (string) $component->short_name;
              $name = empty($name) ? (string) $component->long_name : $name ;
              $name = empty($name) ? ' ' : $name ;
              $address['admin1']   = $name;
              break ;
            case 'locality':
              $address['locality'] = (string) $component->short_name;
              break ;
          }
        }
      }
    }
    return $address;
  }


  static function getGeometry($country, $admin1, $locality){
    $geometry = NULL;
    if(!empty($admin1) && !empty($locality)){
      $input = "$locality, $admin1";
    } elseif (!empty($admin1)){
      $input = $admin1;
    } elseif (!empty($locality)){
      $input = $locality;
    } elseif (isset(Qwik::countries()[$country])) {
      $input = Qwik::countries()[$country];
    } else {
      return;
    } 
    $placeid = self::getPlace($input, $country);
    if(isset($placeid)){
      $details = self::geodetails($placeid);
      if(isset($details)){
        $geometry = $details->geometry;
      }
    }
    return $geometry;
  }

}


Locate::initStatic();

?>
