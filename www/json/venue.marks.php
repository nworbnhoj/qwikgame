<?php
header('Content-Type: application/json');

require_once 'up.php';
require_once PATH_CLASS.'Qwik.php';
require_once PATH_CLASS.'Defend.php';
require_once PATH_CLASS.'Locate.php';
require_once PATH_CLASS.'Mark.php';
require_once PATH_CLASS.'Venue.php';

// key constants
const LAT       = 'lat';
const LNG       = 'lng';
const AVOIDABLE = 'avoidable';
const MARKS     = 'marks';
const GAME      = 'game';
const COUNTRY   = 'country';
const ADMIN1    = 'admin1';
const LOCALITY  = 'locality';
const VENUE     = 'venue';
const REGION    = 'region';
const STATUS    = 'status';
const MSG       = 'msg';

const SUMMARY_THRESHOLD = 100;

$defend = new Defend();
$get = $defend->get();


if(isset($get[GAME])){
  $game = $get[GAME];
} else {
  echo json_encode(array(STATUS=>'error', MSG=>'missing game parameter'));
  Qwik::logMsg("Missing [game] parameter in json call to venue.marks.php");
  return;
}


if (isset($get[REGION])){
  $region = array_reverse(explode('|', $get[REGION]));
  $country  = $region[0];
  $admin1   = isset($region[1]) ? $region[1] : null ;
  $locality = isset($region[2]) ? $region[2] : null ;
  if (!isset($country) && !isset($admin1) && !isset($locality)){
    $errMsg = "region missing country|admin1|locality";
    self::logMsg($errMsg.print_r($get,true));
  }
} elseif (isset($get[LAT]) && isset($get[LNG])){
  // get the region from lat-lng coordinates
  $region = Locate::getAddress($get[LAT], $get[LNG]);
  $locality = isset($region[LOCALITY]) ? $region[LOCALITY] : NULL ;
  $admin1   = isset($region[ADMIN1])   ? $region[ADMIN1]   : NULL ;
  $country  = isset($region[COUNTRY])  ? $region[COUNTRY]  : NULL ;
  if (!isset($country) && !isset($admin1) && !isset($locality)){
    $lat = $get[LAT];
    $lng = $get[LNG];
    $errMsg = "failed to obtain country|admin1|locality for $lat $lng";
  }
} else {
  $errMsg = 'missing region or lat-lng parameters';
  self::logMsg($errMsg.print_r($get,true));
}

if(isset($errMsg)){
  echo json_encode(array(STATUS=>'error', MSG=>$errMsg));
  return;
}


$reply = array(
  STATUS   => 'OK',
  GAME     => $game,
  COUNTRY  => $country,
  ADMIN1   => $admin1,
  LOCALITY => $locality,
  MARKS    => []
);


/******************************************************************************
 * The client can supply a list of "|country|admin1|locality" keys which are
 * already in-hand, and not required in the JSON response.
 *****************************************************************************/
$avoid = isset($get[AVOIDABLE]) ? $get[AVOIDABLE] : '';

$mark = new Mark($game);
$marks = [];
$gotVenues = false;
	
if(required()){
  $marks = array_merge($marks, $mark->getRegionMarks());
}

if($country && required($country)){
  $marks = array_merge($marks, $mark->getRegionMarks($country));
}

if($admin1 && required($country, $admin1)){
  $marks = array_merge($marks, $mark->getRegionMarks($country, $admin1));
}

if($locality && required($country, $admin1, $locality)){
  $marks = array_merge($marks, $mark->getVenueMarks($country, $admin1, $locality));
}


$reply[STATUS] = count($marks) > 0 ? 'OK' : 'NO_RESULTS';
$reply[MARKS] = $marks;


$json = json_encode($reply);

if(!$json){
  $json_error = json_last_error();
  Qwik::logMsg("(venue.markers.php) json error: $json_error\n game = $game");
}
 
echo $json;


/*****************************************************************************/


function required($country=NULL, $admin1=NULL, $locality=NULL){
  global $avoid;
  if(isset($country)){
    if(isset($admin1)){
      if(isset($locality)){
        $key = "$locality|$admin1|$country";
      } else {
        $key = "$admin1|$country";
      }
    } else {
      $key = "$country";
    }
  } else {
    return empty($avoid);
  }
  return !(strpos($avoid, $key) !== FALSE);
}


?>

