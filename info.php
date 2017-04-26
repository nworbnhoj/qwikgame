<?php
	require 'qwik.php';

    $req = validate($_POST);
    if (!$req){
        $req = validate($_GET);
    }

    logReq($req);
	$player = login($req);
    $lang = language($req);

    $qwik = $req['qwik'];
    switch ($qwik){
        case 'feedback':
            qwikContact($req['message'], $req['reply-email']);
            break;
		default:
	}



    $variables = array(
        'INFO_ICON'         => $INFO_ICON,
        'HOME_ICON'         => $HOME_ICON,
        'CROSS_ICON'        => $CROSS_ICON,
        'RELOAD_ICON'       => $RELOAD_ICON,
        'LOGOUT_ICON'       => $LOGOUT_ICON,
		'thumb-up'			=> "<span class='$THUMB_UP_ICON'></span>",
		'thumb-dn'			=> "<span class='$THUMB_DN_ICON'></span>",
        'TWITTER_ICON'      => $TWITTER_ICON,
        'EMAIL_ICON'        => $EMAIL_ICON,
        'FACEBOOK_ICON'     => $FACEBOOK_ICON,
		'LANG_ICON'			=> $LANG_ICON,
		'termsURL'			=> $termsURL,
		'privacyURL'		=> $privacyURL,
    );

    $html = file_get_contents("lang/$lang/info.html");
    $html = replicate($html, $player);
    $html = populate($html, $variables);
    $html = translate($html, $lang);
    echo ($html);

?>
