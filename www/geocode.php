<?php

// Temporary file to change the data structure of venue.xml files for geocoding

require_once 'class/Qwik.php';
require_once 'class/Venue.php';
require_once 'class/LocatePage.php';
require_once 'class/Player.php';

$PATH_VENUE = Qwik::PATH_VENUE;
$XML = Qwik::XML;
$PATH_OUT = "temp";

$venues = Qwik::venues();
foreach($venues as $venue_id){
    $venue_xml = Qwik::readXML($PATH_VENUE, "$venue_id$XML");
    if(isset($venue_xml['id'])){

        $name = (string) $venue_xml['name'];
        $addr = (string) $venue_xml['address'];
        $suburb = (string) $venue_xml['suburb'];
        $state = (string) $venue_xml['state'];
        $country = (string) $venue_xml['country'];

        $description = "$name, $addr, $suburb, $state";
        $address = LocatePage::parseAddress($description, $country);

        $vid = $address ? 
            Venue::venueID($name, $address['locality'], $address['admin1_code'], $address['country_iso']) :
            Venue::venueID($name, $suburb, $state, $country);

        $venue_xml['id'] = $vid;

        if($address){
            $venue_xml['address'] = $address['formatted'];
            $venue_xml['country'] = $address['country_iso'];
            $venue_xml['lat'] = $address['lat'];
            $venue_xml['lng'] = $address['lng'];
            $venue_xml->addAttribute('str-num', $address['str-num']);
            $venue_xml->addAttribute('route', $address['route']);
            $venue_xml->addAttribute('locality', $address['locality']);
            $venue_xml->addAttribute('admin1', $address['admin1_code']);
        } else {
            $words = explode(' ',$addr);
            $num = NULL;
            if(is_numeric($words[0])){
                $num = array_shift($words);
                $venue_xml->addAttribute('str-num', $num);
                $addr = implode(' ',$words);
            }
            $venue_xml->addAttribute('route', $addr);
            $venue_xml->addAttribute('locality', $suburb);
            $venue_xml->addAttribute('admin1', $state);
            $venue_xml['address'] = "$num $addr, $suburb, $state";
        }


//        Qwik::removeElement($venue_xml['suburb']);
//        Qwik::removeElement($venue_xml['state']);


        $fileName = "$vid$XML";
        Qwik::writeXML($venue_xml, $PATH_OUT, $fileName);

        echo "$vid\t\t\t";
        echo $venue_xml['address'];
        echo "\n\t";

        // recast the symlink in the game directories
        $games = $venue_xml->xpath('game');
        foreach($games as $game){
            if(file_exists("$PATH_OUT/$game")
            && !file_exists("$PATH_OUT/$game/$fileName")
            && chdir("$PATH_OUT/$game")){
                symlink("../$fileName", $fileName);
                chdir("../..");
                echo "\t$game";
            } else {
                echo "#$game#";
            }
        }
        echo "\n";

        // rename the venue in all player records
        $pids = $venue_xml->xpath('player');
        foreach($pids as $pid){
            $player = new Player($pid);
            if(isset($player)
            && $player->exists()){
                $player->venueRename($venue_id, $vid);
                $player->save();
                echo "\t\t$pid\n";
            }
        }
        echo "\n";
    }
}

?>
