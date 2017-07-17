<?php
    require 'qwik.php';

    $req = validate($_POST);
    if (!$req){
        $req = validate($_GET);
    }

    logReq($req);
    $player = login($req);

    if (!$player){
        logout();
    }

    $lang = language($req, $player);

	$now = new DateTime('now');
	$compiled = $now->format('d-m-Y');
	$xml = new SimpleXMLElement("<meta date='$compiled'></meta>");

	$venues = venues();
	echo "<table>\n";
	foreach($venues as $vid){
        $venue = readVenueXML($vid);
		if(isset($venue)){
			$lat = $venue['lat'];
			$lng = $venue['lng'];
			$name = $venue['name'];
			echo "<tr><td>$name</td><td>$lat</td><td>$lng</td></tr>\n";
			$v = $xml->addChild('venue', '');
			$v->addAttribute('id', $venue['id']);
            $v->addAttribute('name', $venue['name']);
			$v->addAttribute('lat', $venue['lat']);
			$v->addAttribute('lng', $venue['lng']);
			$players = $venue->xpath("/venue/player");
			if($players){
				$v->addAttribute('playerCount', count($players));
			}
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
	writeXML($path, $filename, $xml);
    writeXML($path, "venues.xml", $xml);


	$html = file_get_contents("$path/$filename");
echo "done";

?>
