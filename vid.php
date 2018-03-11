<?php

// Temporary file to change the id & data structure of venue.xml files for geocoding

    require_once 'class/Qwik.php';
    require_once 'class/Venue.php';
    require_once 'class/VenuePage.php';

    $PATH_VENUE = Qwik::PATH_VENUE;
    $XML = Qwik::XML;
    $PATH_OUT = "temp";

    $venues = Qwik::venues();
    foreach($venues as $vid){
        $venue_xml = Qwik::readXML($PATH_VENUE, "$vid$XML");

        $name = (string) $venue_xml['name'];
        $address = (string) $venue_xml['address'];
        $suburb = (string) $venue_xml['suburb'];
        $state = (string) $venue_xml['state'];
        $country = (string) $venue_xml['country'];

        $vid2 = "$name|$suburb|$state|$country";
        $address2 = "$address, $suburb, $state, $country";

        $venue_xml['id'] = $vid2;
        $venue_xml['address'] = $address2;

	$venue_xml->addAttribute('locality', $suburb);
	$venue_xml->addAttribute('admin1', $state);

        unset($venue_xml['suburb']);
        unset($venue_xml['state']);

        $fileName = "$vid2$XML";
	Qwik::writeXML($venue_xml, $PATH_OUT, $fileName);

        echo "$vid2\n";

        $games = $venue_xml->xpath('game');
        foreach($games as $game){
            if(file_exists("$PATH_OUT/$game")
            && !file_exists("$PATH_OUT/$game/$fileName")
            && chdir("$PATH_OUT/$game")){
                symlink("../$fileName", $fileName);
                chdir("../..");
                echo "\t$game";
            } else {
                echo "\t#$game#";
            }
        }
        echo "\n";
    }

?>
