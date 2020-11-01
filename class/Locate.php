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
            $json = json_decode(Defend::json("$url?$query"), TRUE);
            $status = isset($json['status']) ? $json['status'] : "json reply mising status";
            if($status === 'OK' || $status === 'ZERO_RESULTS'){
                $result = $json;
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
            self::$geoplace->url("json")
        );
    }


    static function geodetails($placeid){
        $geo = self::geo(
            array('place_id'=>$placeid),
            self::$geodetails->key('private'),
            self::$geodetails->url("json")
        );
        return isset($geo['result']) ? $geo['result'] : NULL;
    }


    static function geotime($lat, $lng){
        $location = "$lat,$lng";
        return self::geo(
            array('location'=>$location, 'timestamp'=>time()),
            self::$geotimezone->key('private'),
            self::$geotimezone->url("json")
        );
    }


    static function geocode($address, $country){
        $geo = self::geo(
            array('address'=>"$address", 'components'=>"country:$country"),
            self::$geocode->key('private'),
            self::$geocode->url("json")
        );
        return isset($geo['result']) ? $geo['result'] : NULL;
    }


    static function revgeocode($lat, $lng){
      $type = "country|administrative_area_level_1|locality";
      $geo = self::geo(
        array('latlng'=>"$lat,$lng", 'result_type'=>$type),
        self::$geocode->key('private'),
        self::$geocode->url("json")
      );
      return isset($geo['results']) ? $geo['results'] : NULL;
    }



    static function getPlace($description, $country){
        $placeid = NULL;
        $geoplace = self::geoplace($description, $country);
        if(isset($geoplace['predictions'][0])){
            $place = $geoplace['predictions'][0];
            $placeid = (string) $place['place_id'];
        }
        return $placeid;
    }


    static function getDetails($placeid){
        $details = NULL;
        $result = self::geodetails($placeid);
        if(isset($result)){
          $details = array();
          $details['placeid'] = $placeid;

          $details['name'] = isset($result['name']) ? (string) $result['name'] : '';
          $details['address'] = isset($results['formatted_address']) ? (string) $results['formatted_address'] : '';

          if (isset($result['geometry']['location'])){
            $location = $result['geometry']['location'];
            $lat = (string) $location['lat'];
            $lng = (string) $location['lng'];
            $details['lat'] = $lat;
            $details['lng'] = $lng;
            $details['tz'] = self::getTimezone($lat, $lng);
          }

          if (isset($result['address_components'])){
            $address_components = $result['address_components'];
            foreach($address_components as $addr){
              $types = isset($addr['types']) ? $addr['types'] : array() ;
              foreach($types as $type){
                switch ((string) $type){
                  case 'country':
                    $details['country'] = (string) $addr['long_name'];
                    $details['country_iso'] = (string) $addr['short_name'];
                    break;
                  case 'administrative_area_level_1':
                    $details['admin1'] = (string) $addr['long_name'];
                    $details['admin1_code'] = (string) $addr['short_name'];
                    break;
                  case 'street_number':
                    $details['str-num'] = (string) $addr['long_name'];
                    break;
                  case 'route':
                    $details['route'] = (string) $addr['long_name'];
                    break;
                  case 'locality':
                    $details['locality'] = (string) $addr['long_name'];
                    break;
                  default:
                }
              }
            }
          }

          $details['phone'] = isset($result['phone']) ? (string) $result['phone'] : '';
          $details['phone'] = isset($result['international_phone_number']) ? (string) $result['international_phone_number'] : '';
          $details['url'] = isset($result['website']) ? (string) $result['website'] : '';
        }
        return $details;
    }


    static function getTimezone($lat, $lng){
        $geotime = self::geotime($lat, $lng);
        return isset($geotime['timeZoneId']) ? (string) $geotime['timeZoneId'] : '';
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
            $details = self::geodetails($placeid);
            if(isset($details['geometry']['location'])){
                $loc = $details['geometry']['location'];
                $tz = self::getTimezone($loc['lat'], $loc['lng']);
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
    $revgeocode = self::revgeocode($lat, $lng);
    if(isset($revgeocode[0]['address_components'])){
      $components = $revgeocode[0]['address_components'];
      foreach($components as $component){
        $types = isset($component['types']) ? $component['types'] : array();
        foreach($types as $type){
          switch ((string) $type){
            case 'country':
              $address['country']  = (string) $component['short_name'];
              break ;
            case 'administrative_area_level_1':
              $name = (string) $component['short_name'];
              $name = empty($name) ? (string) $component['long_name'] : $name ;
              $name = empty($name) ? ' ' : $name ;
              $address['admin1']   = $name;
              break ;
            case 'locality':
              $address['locality'] = (string) $component['short_name'];
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
      if (isset($details['geometry'])){
        $geometry = $details['geometry'];
      }
    }
    return $geometry;
  }

}


Locate::initStatic();

?>
