<?php
require_once 'up.php';
require_once PATH_CLASS.'Qwik.php';
require_once PATH_CLASS.'Venue.php';
     
    $game = $_GET['game'];
    $mapData = '';
    if($game){
        $xml = Qwik::readXML(PATH_VENUE, 'venues.xml');
        $venues = $xml->xpath("/meta/venue[game='$game']");
        foreach($venues as $venue){ 
            $vid = $venue['id'];
            $svid = Venue::svid($vid);
            $lat = $venue['lat'];
            $lng = $venue['lng'];
            $name = $venue['name'];
            $count = $venue['playerCount'];
            $mapData .= "{\"vid\": \"$vid\", \"svid\": \"$svid\", \"name\": \"$name\", \"game\": \"$game\", \"lat\": \"$lat\", \"lng\": \"$lng\", \"playerCount\": \"$count\"}, ";
        }

        $mapData = preg_replace('/, $/im', '', $mapData);
        $mapData = "[".$mapData."]";
    }
    echo $mapData;
?>
