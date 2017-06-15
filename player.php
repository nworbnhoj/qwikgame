<?php
	require 'qwik.php';

    $req = validate($_POST);
    if (!$req){
        $req = validate($_GET);
    }

//echo "<br>player<br>";
//print_r($req);
//echo "<br><br>";

	logReq($req);
	$player = login($req);

//print_r($_SESSION);
//echo "<br><br>";

	if (!$player){
		logout();
	}

	$lang = language($req, $player);
	$game = $req['game'];

	if(isset($req['vid'])){
    	$venue = readVenueXML($req['vid']);
	}

    if (isset($venue)){
        if(venueAddGame($venue, $game)){
            writeVenueXML($venue);
            logMsg("Added $game to $vid");
        }
    } elseif (!empty($req['venue'])){
		if(empty($req['repost'])){
			$req['repost'] = 'player.php';
		}
		$query = http_build_query($req);
		header("location: $qwikURL/locate.php?$query");
		return;
	}


	$qwik = $req['qwik'];
	$action = $req['action'];
    switch ($qwik) {
        case "available":
            qwikAvailable($player, $req, $venue);
			break;
        case "keen":	
            qwikKeen($player, $req, $venue);
            break;
        case 'accept':
            qwikAccept($player, $req);
             break;
        case 'decline':
            qwikDecline($player, $req);
             break;
        case 'familiar':
            qwikFamiliar($player, $req);
            break;
        case 'region':
            qwikRegion($player, $req);
            break;
        case "cancel":
            qwikCancel($player, $req);
            break;
        case "feedback":
          	qwikFeedback($player, $req);
            break;
		case 'delete':
           	qwikDelete($player, $req);
          		break;
		case 'account':
			qwikAccount($player, $req);
			break;
		case 'msg':
			qwikMsg($player, $req);
			break;
      	case 'login':
			if(isset($req['email'])){
				$email = $req['email'];
                if($email != (string) $player->email()){
                    $player->email($email);
                    $token = $player->token(Player::MINUTE);
                    $newID = $player->id();
                    $query = "qwik=login&pid=$newID&token=$token'";
                    header("Location: $qwikURL/player.php?$query");
                }
			}
			break;
		case 'logout':
			logout();
			break;
       	default:
//     		header("Location: error.php?msg=<b>Invalid post:<b> $qwik<br>");
	}

	$player->concludeMatches();

    $rnd = mt_rand(1,8);
	$message = isset($player->email) ? 
		"<t>Tip$rnd</t>" :
		'Please <b>activate</b> your account<br><br>An email has been sent with an activation link to click.';

	$hourRows = '';
    $days = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');
    $tabs = "\t\t\t\t";
    foreach($days as $day){
        $bit = 1;
        $hourRows .= "$tabs<tr>\n";
        $hourRows .= "$tabs\t<input name='$day' type='hidden' value='0'>\n";
        $hourRows .= "$tabs\t<th>$day</th>\n";
        for($hr24=0; $hr24<=23; $hr24++){
            if (($hr24 < 6) | ($hr24 > 20)){
                $hidden = 'hidden';
            } else {
                $hidden = '';
            }
            if ($hr24 <= 12){
                $hr12 = $hr24;
            } else {
                $hr12 = $hr24-12;
            }
            $hourRows .= "$tabs\t<td class='toggle' bit='$bit' $hidden>$hr12</td>\n";
            $bit = $bit * 2;
        }
        $hourRows .= "$tabs</tr>\n";
    }

    $familiarCheckboxes = familiarCheckboxes($player);

    $variables = array(
        'pid'               => $player['id'],
        'vid'               => $venue['id'],
		'venue'				=> isset($req['venue']) ? $req['venue'] : '',
		'message'			=> $message,
        'game'              => $games["$game"],
		'playerName'		=> empty($player['nick']) ? $player->email : $player['nick'],
		'gameOptions'		=> gameOptions($game, "\t\t"),
		'familiarHidden'	=> empty($familiarCheckboxes) ? 'hidden' : ' ',
		'hourRows'			=> $hourRows,
		'selectRegion'		=> $selectRegion,
		'regionOptions'		=> regionOptions($player, "\t\t\t"),
		'historyHidden'		=> count($player->xpath("match[@status='history']")) == 0 ? 'hidden' : '',
		'historyForms'		=> $historyForms,
		'reputation'		=> repWord($player),
		'reputationLink'	=> "<a href='info.php#reputation'>reputation</a>",
		'thumbs'			=> repThumbs($player),
		'playerNick'		=> isset($player['nick']) ? $player['nick'] : '',
		'playerURL'			=> isset($player['url']) ? $player['url'] : '',
		'playerEmail'		=> $player->email,
		'datalists'			=> datalists(),
        'INFO_ICON'         => $INFO_ICON,
        'HOME_ICON'         => $HOME_ICON,
		'CROSS_ICON'		=> $CROSS_ICON,
		'RELOAD_ICON'		=> $RELOAD_ICON,
		'LOGOUT_ICON'		=> $LOGOUT_ICON,
        'TWITTER_ICON'      => $TWITTER_ICON,
        'EMAIL_ICON'        => $EMAIL_ICON,
        'FACEBOOK_ICON'     => $FACEBOOK_ICON,
		'MAP_ICON'			=> $MAP_ICON,
		'SEND_ICON'			=> $SEND_ICON,
    );

    $html = file_get_contents("lang/$lang/player.html");
//	do{
		$html = replicate($html, $player);
	    $html = populate($html, $variables);
	    $html = translate($html, $lang);
//	} while (preg_match("\<v\>([^\<]+)\<\/v\>", $html) != 1);
//	} while (strstr($html, "<v>"));
    echo ($html);

?>

