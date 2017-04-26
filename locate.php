<?php
    require 'qwik.php';

    $req = validate($_POST);
    if (!$req){
        $req = validate($_GET);
    }

// echo "<br><br>";
// print_r($req);
// echo "<br><br>";


    if(!$req
    || empty($req['game'])){
//    || empty($req['venue'])){
        header("Location: $qwikURL");
//        invalidRequest($_POST, $_GET, 'malformed request');
 //       return;
    }


    logReq($req);
    $player = login($req);

    $venueDesc = $req['venue'];
	$repost = $req['repost'];

	$vids = matchShortVenueID($venueDesc);
	$matchCount = count($vids);
	if($matchCount == 1){
		$req['vid'] = $vids[0];
		$query = http_build_query($req);
        header("location: $qwikURL/$repost?$query");
		return;
	}

    $lang = language($req, $player);

	$game = $req['game'];

	if(isset($req['name'])
	&& isset($req['address'])
	&& isset($req['suburb'])
	&& isset($req['state'])
	&& isset($req['country'])){
		$vid = venueID(
			$req['name'], 
			$req['address'], 
        	$req['suburb'], 
        	$req['state'], 
        	$req['country']
		);
		$xml = "<venue id='$vid'><game>$game</game></venue>";
		$venue = new simplexmlelement($xml, LIBXML_NOENT);
		updateVenue($venue, $req);

        $req['vid'] = $vid;
        $query = http_build_query($req);
        header("location: $qwikURL/$repost?$query");
        return;
	}

	$venue = newVenue($venueDesc);

    $variables = array(
        'vid'               => $venue['id'],
        'game'              => $game,
        'homeURL'           => "$qwikURL/player.php",
		'repost'			=> $repost,
        'venueName'         => isset($venue['name']) ? $venue['name'] : '',
        'venueAddress'      => isset($venue['address']) ? $venue['address'] : '',
        'venueSuburb'       => isset($venue['suburb']) ? $venue['suburb'] : '',
        'venueState'        => isset($venue['state']) ? $venue['state'] : '',
        'venueCountry'      => $venueCountry,
        'countryOptions'    => countryOptions($venueCountry, "\t\t\t\t\t"),
        'venuePhone'        => isset($venue['phone']) ? $venue['phone'] : '',
        'venueURL'          => isset($venue['url']) ? $venue['url'] : '',
        'venueTZ'           => $venue['tz'],
        'venueLat'          => isset($venue['lat']) ? $venue['lat'] : '',
        'venueLng'          => isset($venue['lng']) ? $venue['lng'] : '',
        'venueNote'         => isset($venue['note']) ? $venue['note'] : ' ',
        'venueRevertDiv'    => venueRevertDiv($venue),
        'backLink'          => $backLink,
        'venueUrlLink'      => "<a href='$venueUrl'><t>homepage</t></a>",
        'INFO_ICON'         => $INFO_ICON,
        'HOME_ICON'         => $HOME_ICON,
        'TWITTER_ICON'      => $TWITTER_ICON,
        'EMAIL_ICON'        => $EMAIL_ICON,
        'FACEBOOK_ICON'     => $FACEBOOK_ICON,
    );

    $html = file_get_contents("lang/$lang/locate.html");
	$html = replicate($html, $player, $req);
    $html = populate($html, $variables);
    $html = translate($html, $lang);
    echo ($html);

?>
