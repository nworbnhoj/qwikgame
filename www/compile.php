<?php
require_once 'up.php';
require_once PATH_CLASS.'Qwik.php';
require_once PATH_CLASS.'Venue.php';
require_once PATH_CLASS.'Page.php';

    $now = new DateTime('now');
    $compiled = $now->format('d-m-Y');
    $xml = new SimpleXMLElement("<meta date='$compiled'></meta>");

    $venues = Qwik::venues();
    $rows = "";
    foreach($venues as $vid){
        try {
            $venue = new Venue($vid, FALSE);
            if(isset($venue)){
                $lat = $venue->lat();
                $lng = $venue->lng();
                $name = $venue->name();
                $rows .= "<tr><td>$name</td><td>$lat</td><td>$lng</td></tr>\n";
                $v = $xml->addChild('venue', '');
                $v->addAttribute('id', $venue->id());
                $v->addAttribute('name', $venue->name());
                $v->addAttribute('lat', $venue->lat());
                $v->addAttribute('lng', $venue->lng());
                $v->addAttribute('playerCount', $venue->playerCount());
            }
        } catch (RuntimeException $e){
            self::logThrown($e);
        }
    }
    $table = "<table>\n$rows</table>\n";
    echo $table;

    $games = Qwik::qwikGames();
    foreach($games as $game => $name){
        $venues = Qwik::venues($game);
        foreach($venues as $vid){
            $vids = $xml->xpath("venue[@id='$vid']");
            if (isset($vids[0])){
                $vids[0]->addChild("game", $game);
            }
        }
    }

    $path = $up.Qwik::PATH_VENUE;
    $filename = "venues$compiled.xml";
    $xml->saveXML("$path/$filename");
    $xml->saveXML("$path/venues.xml");


    $html = file_get_contents("$path/$filename");

    echo "\n\n========================\n\n";
    echo "done";

?>
