<?php
	 require_once '../qwik.php';
	 require_once '../class/Venue.php';
	 
	$game = $_GET['game'];
	$mapData = '';
	if($game){
		$xml = readXML('../venue', 'venues.xml');
		$venues = $xml->xpath("/meta/venue[game='$game']");
		foreach($venues as $venue){ 
			$vid = $venue['id'];
			$svid = Venue::svid($vid);
			$lat = $venue['lat'];
			$lng = $venue['lng'];
			$name = $venue['name'];
			$info = "<b>$name</b><br><a href='venue.php?vid=$vid'>more...</a><br>play <a href='player.php#matches?game=$game&venue=$svid'>now</a> or <a href='player.php#available?game=$game&venue=$svid'>later</a>";
			$count = $venue['playerCount'];
			$mapData .= "{\"vid\": \"$vid\", \"svid\": \"$svid\", \"name\": \"$name\", \"game\": \"$game\", \"lat\": \"$lat\", \"lng\": \"$lng\", \"playerCount\": \"$count\"}, ";

		}
	
		$mapData = preg_replace('/, $/im', '', $mapData);
		$mapData = "[".$mapData."]";
	}	
	echo $mapData;
?>
