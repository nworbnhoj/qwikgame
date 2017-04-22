<?php
    require 'qwik.php';

    $req = validate($_POST);
    if (!$req){
        $req = validate($_GET);
    }

    logReq($req);
    $player = login($req);
	$lang = language($req, $player);


	if ($player && isset($req['qwik'])){
		$qwik = $req['qwik'];

        switch ($qwik) {
            case 'upload':
				if(isset($req['game'])
			    && isset($req['title'])){
			        $game = $req['game'];
			        $title = $req['title'];
			        $msg = uploadRanking($player, $game, $title);
				}
	            break;

            case "activate":
                if(isset($req['filename'])){
					$fileName = $req['filename'];
					$ranking = getUpload($player, $fileName);
					insertRanking($ranking);
				}
    	        break;
			case 'deactivate':
                if(isset($req['filename'])){
                    $fileName = $req['filename'];
                    $ranking = getUpload($player, $fileName);
                    extractRanking($ranking);
                }
				break;

            case 'delete':
                if(isset($req['filename'])){
                    $fileName = $req['filename'];
                    deleteUpload($player, $fileName);
                }
            	break;
		}
	}


    $variables = array(
		'please_login'	=> $player ? '' : '<t>prompt_login</t>',
    	'uploadHidden'	=> $player ? '' : 'hidden',
		'uploadDisabled'	=> $player ? '' : 'disabled',
        'INFO_ICON'     => $INFO_ICON,
        'HOME_ICON'     => $HOME_ICON,
		'TICK_ICON'		=> $TICK_ICON,
        'CROSS_ICON'    => $CROSS_ICON,
        'RELOAD_ICON'   => $RELOAD_ICON,
        'LOGOUT_ICON'   => isset($player) ? $LOGOUT_ICON : '',
    );

    $html = file_get_contents("$lang/upload.html");
    $html = replicate($html, $player);
    $html = populate($html, $variables);
    $html = translate($html, $lang);
    echo ($html);
?>
