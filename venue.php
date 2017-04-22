<?php
	require 'qwik.php';

	$req = validate($_POST);
	if (!$req){
		$req = validate($_GET);
	}

	if(!$req 
	|| empty($req['vid'])){
		header("Location: $qwikURL");		
//        invalidRequest($_POST, $_GET, 'malformed request');
        return;
	}

// echo "<br><br>";
// print_r($req);
// echo "<br><br>";

    logReq($req);
    $player = login($req);

    $lang = language($req, $player);

	$game = $req['game'];

	$vid = $req['vid'];
    $venue=readVenueXML($vid);

    if(!$venue){
		header("Location: $qwikURL");
		return;
    }

    if($player
	&& isset($req['name'])
    && isset($req['address'])
    && isset($req['suburb'])
    && isset($req['state'])
    && isset($req['country'])){
		updateVenue($venue, $req);
	}

    concludeReverts($venue);

    if(!$newVenue){
		$message = "";
        $displayHidden='';
        $editHidden='hidden';
        $venueName = $venue['name'];
    } elseif(empty($venueSimilarDiv)) {
		$message = "<b>Please provide some additional details about this new venue.</b>";
		$displayHidden = 'hidden';
        $editHidden = '';
        $venueName = $description;
	} else {
        $message = "<b>Please select an existing venue or create a new one.</b>";
        $displayHidden = 'hidden';
        $editHidden = '';
        $venueName = $description;
    }

	$venueUrl = $venue['url'];
    $venueState = (empty($venue['state'])) ? geolocate('region') : $venue['state'];
	$venueCountry = $venue['country'];
	$backLink = "<a href='$qwikURL/index.php?venue=$venueName&game=$game' target='_blank'><b>link</b></a>";

	$variables = array(
		'vid'			=> $venue['id'],
		'game'			=> $game,
		'homeURL'		=> $qwikURL,
		'playerCount'	=> count($venue->xpath('player')),
		'message'		=> $message,
		'displayHidden'	=> $displayHidden,
		'editHidden'	=> $editHidden,
		'repostInputs'	=> repostIns($repost, "\t\t\t"),
		'venueName'		=> $venueName,
        'venueAddress'  => isset($venue['address']) ? $venue['address'] : '',
        'venueSuburb'	=> isset($venue['suburb']) ? $venue['suburb'] : '',
        'venueState' 	=> $venueState,
        'venueCountry'  => $venueCountry,
		'countryOptions'=> countryOptions($venueCountry, "\t\t\t\t\t"),
		'venuePhone'	=> isset($venue['phone']) ? $venue['phone'] : '',
        'venueURL'		=> isset($venue['url']) ? $venue['url'] : '',
		'venueTZ'		=> isset($venue['tz']) ? $venue['tz'] : '',
		'venueLat'		=> isset($venue['lat']) ? $venue['lat'] : '',
		'venueLng'		=> isset($venue['lng']) ? $venue['lng'] : '',
        'venueNote'		=> isset($venue['note']) ? $venue['note'] : ' ',
		'venueRevertDiv'=> venueRevertDiv($venue),
		'backLink'		=> $backLink,
		'venueUrlLink'	=> "<a href='$venueUrl'><t>homepage</t></a>",
		'INFO_ICON'		=> $INFO_ICON,
        'HOME_ICON'     => $HOME_ICON,
		'TWITTER_ICON'	=> $TWITTER_ICON,
		'EMAIL_ICON'	=> $EMAIL_ICON,
		'FACEBOOK_ICON'	=> $FACEBOOK_ICON,
	);

	$html = file_get_contents("$lang/venue.html");
	$html = populate($html, $variables);
	$html = translate($html, $lang);
	echo ($html);

?>
