<?php
    require 'qwik.php';

    $req = validate($_POST);
    if (!$req){
        $req = validate($_GET);
    }

    logReq($req);
    $player = login($req);
    $lang = language($req, $player);
    $game = $req['game'];

    $variables = array(
        'game'              => $games["$game"],
		'homeURL'			=> $qwikURL,
        'INFO_ICON'         => $INFO_ICON,
        'HOME_ICON'         => $HOME_ICON,
        'CROSS_ICON'        => $CROSS_ICON,
        'RELOAD_ICON'       => $RELOAD_ICON,
        'COMMENT_ICON'      => $COMMENT_ICON,
        'MALE_ICON'         => $MALE_ICON,
        'FEMALE_ICON'       => $FEMALE_ICON,
        'THUMB_UP_ICON'     => $THUMB_UP_ICON,
        'THUMB_DN_ICON'     => $THUMB_DN_ICON,
        'LANG_ICON'         => $LANG_ICON,
        'INFO_ICON'         => $INFO_ICON,
        'LOGOUT_ICON'       => isset($player) ? $LOGOUT_ICON : '',
        'TWITTER_ICON'      => $TWITTER_ICON,
        'EMAIL_ICON'        => $EMAIL_ICON,
        'FACEBOOK_ICON'     => $FACEBOOK_ICON,
        'HOME_ICON'        => $HOME_ICON,
    );
    $html = file_get_contents("lang/$lang/venues.html");
    $html = replicate($html, $player, $req);
    $html = populate($html, $variables);
    $html = translate($html, $lang);
    echo ($html);
