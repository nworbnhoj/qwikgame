<?php

/******************************************************************************
 * cron update Venue details from Google Maps API.
 * 
 * Intended to be run regularly as a cron job
 *     * * * * 1 cd /usr/share/nginx/qwikgame.org/www/; php -q cron/venue_details_update.php
 * 
 *****************************************************************************/

require_once 'up.php';
require_once PATH_CLASS.'Qwik.php';
require_once PATH_CLASS.'Venue.php';

Qwik::logMsg("Venue details update starting");

$venue_files = Qwik::fileList(PATH_VENUE);
foreach($venue_files as $file){
  if(!is_file(PATH_VENUE.$file)){ continue; }
  if(!str_ends_with($file, ".xml")){ continue; }

  $vid = substr($file, 0, strlen($file) - 4);
  if(!mb_ereg_match("^([\w\- _&,./@]+[|]){0,3}[A-Z]{2}$", $vid)){
    Qwik::logMsg("Venue bad vid: $vid");
    continue;
  }
  if(!Venue::exists($vid)){
    Qwik::logMsg("Venue missing: $vid");
    continue;
  }
  
  $venue = new Venue($vid);
  $placeid = $venue->placeid();
  if(empty($placeid)){
    Qwik::logMsg("Venue missing placeid: $vid");
    continue;
  }

  $details = Locate::getDetails($placeid);
  if($details){ 
    if ($venue->furnish($details)) {
      Qwik::logMsg("Venue details updated: $vid");
    }
  }
}

Qwik::logMsg("Venue details update complete");

?>
