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
    $venue = new Venue($vid, $log);

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
		$venue->update($req);
	}

    $venue->concludeReverts();

    if(!$newVenue){
		$message = "";
        $displayHidden='';
        $editHidden='hidden';
        $venueName = $venue->name();
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

	$venueUrl = $venue->url();
    $venueState = (empty($venue->state())) ? geolocate('region') : $venue->state();
	$venueCountry = $venue->country();
	$backLink = "<a href='$qwikURL/index.php?venue=$venueName&game=$game' target='_blank'><b>link</b></a>";

	$variables = array(
		'vid'			=> $venue->id(),
		'game'			=> $game,
		'homeURL'		=> $qwikURL,
		'playerCount'	=> $venue->playerCount(),
		'message'		=> $message,
		'displayHidden'	=> $displayHidden,
		'editHidden'	=> $editHidden,
		'repostInputs'	=> repostIns($repost, "\t\t\t"),
		'venueName'		=> $venueName,
        'venueAddress'  => $venue->address(),
        'venueSuburb'	=> $venue->suburb(),
        'venueState' 	=> $venueState,
        'venueCountry'  => $venueCountry,
		'countryOptions'=> countryOptions($venueCountry, "\t\t\t\t\t"),
		'venuePhone'	=> $venue->phone(),
        'venueURL'		=> $venue->url(),
		'venueTZ'		=> $venue->tz(),
		'venueLat'		=> $venue->lat(),
		'venueLng'		=> $venue->lng(),
        'venueNote'		=> $venue->note(),
		'venueRevertDiv'=> $venue->revertDiv(),
		'backLink'		=> $backLink,
		'venueUrlLink'	=> "<a href='$venueUrl'><t>homepage</t></a>",
		'INFO_ICON'		=> $INFO_ICON,
        'HOME_ICON'     => $HOME_ICON,
		'TWITTER_ICON'	=> $TWITTER_ICON,
		'EMAIL_ICON'	=> $EMAIL_ICON,
		'FACEBOOK_ICON'	=> $FACEBOOK_ICON,
	);

	$html = file_get_contents("lang/$lang/venue.html");
	$html = populate($html, $variables);
	$html = translate($html, $lang);
	echo ($html);

?>
