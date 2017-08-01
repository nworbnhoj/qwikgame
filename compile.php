<?php
    require_once 'qwik.php';
    require_once 'class/Venue.php';
    require_once 'class/Page.php';

	$now = new DateTime('now');
	$compiled = $now->format('d-m-Y');
	$xml = new SimpleXMLElement("<meta date='$compiled'></meta>");

	$venues = venues();
	echo "<table>\n";
	foreach($venues as $vid){
        $venue = new Venue($vid, Page::$log, FALSE);
		if(isset($venue)){
			$lat = $venue->lat();
			$lng = $venue->lng();
			$name = $venue->name();
			echo "<tr><td>$name</td><td>$lat</td><td>$lng</td></tr>\n";
			$v = $xml->addChild('venue', '');
			$v->addAttribute('id', $venue->id());
            $v->addAttribute('name', $venue->name());
			$v->addAttribute('lat', $venue->lat());
			$v->addAttribute('lng', $venue->lng());
            $v->addAttribute('playerCount', $venue->playerCount());
   	    }
	}
	echo "</table>";

	foreach($games as $game => $name){
		$venues = venues($game);
        foreach($venues as $vid){
			$v = $xml->xpath("venue[@id='$vid']")[0];
			if($v){
				$v->addChild("game", $game);
			}
		}
	}

	$path = "venue";
	$filename = "venues$compiled.xml";
	$xml->saveXML("$path/$filename");
	$xml->saveXML("$path/venues.xml");


	$html = file_get_contents("$path/$filename");
echo "done";

?>
