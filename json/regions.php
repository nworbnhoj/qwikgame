<?php

	$pid = $req['pid'];
	$player = readPlayerXML($pid);
	$options = '';
	if(isset($player){	

	    $available = $player->xpath('available');
	    $regions = array();
	    foreach($available as $avail){
	        $venueID = $avail->venue;
	        $reg = explode(', ', $venueID);
	        $last = count($reg);
	        $regions[] = $reg[$last-1];
	        $regions[] = $reg[$last-2];
	        $regions[] = $reg[$last-3];
	    }
	    $regions = array_unique($regions);

	    $options = '';
	    foreach($regions as $region){
	        $options .= "$tabs<option value='$region'>$region</option>\n";
	    }
	}

   	echo $options;

?>
