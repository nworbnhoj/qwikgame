<?php
    require 'qwik.php';

    $req = validate($_POST);
    if (!$req){
        $req = validate($_GET);
    }

    logReq($req);
	$player = login($req);
	if (isset($player)){
		$query = http_build_query($req);
		header("Location: $qwikURL/player.php?$query");
		return;
	}

	$qwik = $req['qwik'];
	if ($qwik == 'available'
	&& isset($req['venue']) 
	&& isset($req['game'])
	&& isset($req['email'])){
		$email = $req['email'];
		$pid = anonID($email);
		$anon = readPlayerXML($pid);
		if(isset($anon)){
			$token = newPlayerToken($anon, 2*$DAY);
			$req['pid'] = $pid;
			$req['token'] = $token;
			$req['repost'] = 'player.php#available';
			emailStash($email, 'locate.php', $req, $pid, $token);
		}
	}

    $lang = language($req, $player);
    $venue = $req['venue'];
    $game = $req['game'];

    $variables = array(
		'playerCount'		=> countFiles('player'),
		'venueCount'		=> countFiles('venue'),
		'venuesLink'		=> "<a href='venues.php?game=squash'><t>venues</t></a>",
        'venue'             => isset($venue) ? $venue : '',
        'message'           => $message,
        'game'              => $games["$game"],
//		'game'				=> $game,
        'gameOptions'       => gameOptions($game, "\t\t"),
        'datalists'         => datalists(),
        'INFO_ICON'         => $INFO_ICON,
        'HOME_ICON'         => $HOME_ICON,
        'CROSS_ICON'        => $CROSS_ICON,
        'RELOAD_ICON'       => $RELOAD_ICON,
		'COMMENT_ICON'		=> $COMMENT_ICON,
		'MALE_ICON'			=> $MALE_ICON,
		'FEMALE_ICON'		=> $FEMALE_ICON,
		'THUMB_UP_ICON'		=> $THUMB_UP_ICON,
        'THUMB_DN_ICON'     => $THUMB_DN_ICON,
        'LANG_ICON' 		=> $LANG_ICON,
		'INFO_ICON'			=> $INFO_ICON,
		'LOGOUT_ICON'		=> isset($player) ? $LOGOUT_ICON : '',
        'TWITTER_ICON'      => $TWITTER_ICON,
        'EMAIL_ICON'        => $EMAIL_ICON,
        'FACEBOOK_ICON'     => $FACEBOOK_ICON,
    );

    $html = file_get_contents("$lang/index.html");
    $html = replicate($html, $player);
    $html = populate($html, $variables);
    $html = translate($html, $lang);
    echo ($html);


	
?>


