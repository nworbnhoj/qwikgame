<?php 

$subdomain = 'www';
$qwikURL = "http://$subdomain.qwikgame.org";


include 'Player.php';
include 'logging.php'; 
$log = new Logging();
$log->lfile("/tmp/$subdomain.qwikgame.org.log");


$languages = array(
    'zh'=>'中文',
    'es'=>'Español',
    'en'=>'English',
//    'fr'=>'français',
//    'hi'=>'हिन्दी भाषा',
//    'ar'=>'اللغة العربية',
//    'jp'=>'日本語'
);


// include language translations
foreach($languages as $code => $language){
    include "lang/$code.php";
}


$flyerURL = "$qwikURL/pdf/qwikgame.org%20flyer.pdf";
$termsURL = "$qwikURL/pdf/qwikgame.org%20terms%20and%20conditions.pdf";
$privacyURL = "$qwikURL/pdf/qwikgame.org%20privacy%20policy.pdf";
$facebookURL = "https://www.facebook.com/sharer/sharer.php?u=$qwikURL";
$twitterURL = "https://twitter.com/intent/tweet?text=<t>tagline</t>&url=$qwikURL";


$emailImg = "<img src='img/email.png' alt='email' class='socialmedia'>";
$facebookImg = "<img src='img/facebook.png' alt='facebook' class='socialmedia'>";
$twitterImg = "<img src='img/twitter.png' alt='twitter' class='socialmedia'>";

$emailLink = "<a href='mailto:?subject=$qwikURL&body=$qwikURL%20makes%20it%20easy%20to%20<t>tagline</t>&target=_blank'>$emailImg</a>";
$facebookLink = "<a href='$facebookURL' target='_blank'>$facebookImg</a>";
$twitterLink = "<a href='$twitterURL' target='_blank'>$twitterImg</a>";


$INFO_ICON      = 'fa fa-question-circle icon';
$HOME_ICON      = 'fa fa-home icon';
$RELOAD_ICON    = 'fa fa-refresh icon';
$BACK_ICO       = 'fa fa-chevron-circle-left icon';
$LOGOUT_ICON    = 'fa fa-power-off icon';
$TICK_ICON      = 'fa fa-check-circle tick';
$CROSS_ICON     = 'fa fa-times-circle cross';
$THUMB_UP_ICON  = 'fa fa-thumbs-o-up thumb green';
$THUMB_DN_ICON  = 'fa fa-thumbs-o-down thumb red';
$TWITTER_ICON   = 'fa fa-twitter icon';
$MALE_ICON      = 'fa fa-male person';
$FEMALE_ICON    = 'fa fa-female person';
$COMMENT_ICON   = 'fa fa-comment-o comment';
$LANG_ICON      = 'fa fa-globe icon';
$MAP_ICON       = 'fa fa-map-marker';
$SEND_ICON      = 'fa fa-send';



$CC_ICON_LINK = "
    <a rel='license' href='http://creativecommons.org/licenses/by/4.0/'>
        <img alt='Creative Commons License' 
            style='border-width:0' 
            src='https://i.creativecommons.org/l/by/4.0/88x31.png' />
    </a>";
$CC_ATTR_LINK = "
    <a xmlns:cc='http://creativecommons.org/ns#' 
        href='qwikgame.org' 
        property='cc:attributionName' 
        rel='cc:attributionURL'>
        qwikgame.org
    </a>";
$CC_LICENCE_LINK = "
    <a rel='license' 
        href='http://creativecommons.org/licenses/by/4.0/'>
        <t>Creative Commons Attribution 4.0 International License</t>
    </a>";


$geo;
$star = '★';
$stars = array("","$star","$star$star","$star$star$star","$star$star$star$star","$star$star$star$star$star");
$clock24hr = FALSE;
$tick = 'fa-check-circle';
$cross = 'fa-times-circle';
$help = 'fa-question-circle';
$home = 'fa-home';
$reload = 'fa-refresh';
$revert = '⟲';
$back = '⤺';
$bug = '☹';
$logout = 'fa-power-off';

$qwiks=array('accept','account','activate','available','cancel','deactivate','decline','delete','familiar','feedback','keen','login','logout','msg','recover','region','upload');

$parityExp=array();
$parityExp[-2]    = 'much weaker';
$parityExp[-1] = 'weaker';
$parityExp[0]    = 'well matched';
$parityExp[1]    = 'stronger';
$parityExp[2]    = 'much stronger';

$parityFilter = array('any','similar','matching', '-2', '-1', '0', '1', '2');



$games = array(
    'backgammon'    => '<t>Backgammon</t>',
    'badminton'        => '<t>Badminton</t>',
    'boules'        => '<t>Boules</t>',
    'billards'        => '<t>Billiards</t>',
    'checkers'        => '<t>Checkers</t>',
    'chess'         => '<t>Chess</t>',
    'cycle'            => '<t>Cycle</t>',
    'darts'            => '<t>Darts</t>',
    'dirt'            => '<t>Dirt Biking</t>',
    'fly'            => '<t>Fly Fishing</t>',
    'go'            => '<t>Go</t>',
    'golf'          => '<t>Golf</t>',
    'lawn'            => '<t>Lawn Bowls</t>',
    'mtnbike'        => '<t>Mountain_Biking</t>',
    'pool'            => '<t>Pool</t>',
    'racquetball'   => '<t>Racquetball</t>',
    'run'            => '<t>Run</t>',
    'snooker'        => '<t>Snooker</t>',
    'squash'        => '<t>Squash</t>',
    'table'            => '<t>Table_Tennis</t>',
    'tennis'        => '<t>Tennis</t>',
    'tenpin'        => '<t>Tenpin</t>',
    'walk'            => '<t>Walk</t>'
);

$status = array(
    'keen'        => 1,
    'invitation'=> 2,
    'accepted'    => 3,
    'confirmed'    => 4,
    'feedback'    => 5,
    'history'    => 6,
    'cancelled'    => 10
);



function logg($msg){
    global $log;
    $log->lwrite($msg);
    $log->lclose();
}

function logReq($req){
    global $log;
    $msg = '';
    foreach($req as $key => $val){
        $msg .= " $key=";
        switch($key){
            case 'pid':
                $msg .= substr($val, 0, 4);
            break;
            case 'token':
                $msg .= substr($val, 0, 2);
            break;
            default:
                $msg .= $val;
        }
    }
    $log->lwrite($msg);
    $log->lclose();
}

function logEmail($type, $pid, $game, $vid, $time){
    global $log;
    $p = substr($pid, 0, 4);
    $msg = "email $type pid=$p $game $vid $time";
    $log->lwrite($msg);
    $log->lclose();
}


function logMsg($msg){
    global $log;
    $log->lwrite($msg);
    $log->lclose();
}



# SECURITY escape all parameters to prevent malicious code insertion
# http://au.php.net/manual/en/function.htmlentities.php
function SECURITYsanitizeHTML($data){
    if (is_array($data)){
        foreach($data as $key => $val){
            $data[$key] = SECURITYsanitizeHTML($val);
        }
    } else {
        $data = htmlentities(trim($data), ENT_QUOTES | ENT_HTML5);
    }
    return $data;
}


// https://stackoverflow.com/questions/5647461/how-do-i-send-a-post-request-with-php
function post($url, $data){
    // use key 'http' even if you send the request to https://...
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */ }
    
    var_dump($result);
}


/********************************************************************************
Return the XML data for the current logged in player (if any)

$req    ArrayMap    url parameters from post&get
********************************************************************************/
function login($req){
    session_start();

    if (isset($req['pid'])){            // check for a pid & token in the parameter
        $pid = $req['pid'];
        $token = $req['token'];
    } elseif (isset($_SESSION['pid'])){ // check for a pid in the $_SESSION variable
        $pid = $_SESSION['pid'];
        $openSession = true;
    } elseif (isset($_COOKIE['pid'])){  // check for a pid & token in a $_COOKIE
        $pid = $_COOKIE['pid'];
        $token = $_COOKIE['token'];
    } elseif (isset($req['email'])){    // check for and email address in the param
        $email = $req['email'];
        $pid = anonID($email);          // and derive the pid from the email
        $token = $req['token'];
    } else {                            // anonymous session: no player identifier
        return;                         // RETURN login fail
    }
                                        // OK playerID
    $player = new Player($pid, $log, TRUE);

    if($openSession){
        return $player;
    }

    if($player->isValidToken($token)){                 // LOGIN with token
        logMsg("login: valid token " . snip($pid));
        $_SESSION['pid'] = $pid;
        $_SESSION['lang'] = (string) $player->lang();
        setcookie("pid", "$pid", time() + 3*Player::MONTH, "/");
        setcookie("token", "$token", time() + 3*Player::MONTH, "/");
        return $player;
    } else {
        logMsg("login: invalid token pid=" . snip($pid));
    }

    if(empty($player->email()) && isset($email)){            // LOGIN anon player
        logMsg("login: anon player " . snip($pid));
        emailWelcome($email, $pid, $player->token(Player::MONTH));
        setcookie("pid", '', time()-Player::DAY, "/");
        setcookie("token", '', time()-Player::DAY, "/");
        $_SESSION['pid'] = $pid;
        $_SESSION['lang'] = (string) $player->lang();
        return $player;
    }

    if(isset($email) && $req['qwik'] == 'recover'){            // account recovery
        logMsg("login: recover account " . snip($pid));                 // todo rate limit
        emailLogin($email, $pid, $player->token(Player::DAY));
    }
}


/********************************************************************************
Logout the current player by deleting both the $_SESSION and the longer term 
$_COOKIE
********************************************************************************/
function logout(){
    $pid = $_SESSION['pid'];
    logMsg("logout $pid");
    global $qwikURL;
    unset($_SESSION['pid']);
    setcookie("pid", "", time() - Player::DAY);
    setcookie("token", "", time() - Player::DAY);
    header("location: $qwikURL");
}


/********************************************************************************
Return the current player language or default

$req    ArrayMap    url parameters from post&get
$player    XML            player data
********************************************************************************/
function language($req, $player){
    global $languages;
    header('Cache-control: private'); // IE 6 FIX

    if(isset($req['lang'])                            // REQUESTED language
    && array_key_exists($req['lang'], $languages)){
        $lang = $req['lang'];
        if (isset($player)){
            $player->lang($lang);
            $player->save();
        }
    } elseif (isset($_SESSION['lang'])                // SESSION language
    && array_key_exists($_SESSION['lang'], $languages)){
        $lang = $_SESSION['lang'];
    } elseif ($player                                 // USER language
    && (null !== $player->lang())
    && array_key_exists($player->lang(), $languages)){
        $lang = (string) $player->lang();
    } elseif (false){                                // geolocate language
        // todo code
    } else {                                        // default english
        $lang = 'en';
    }

    $_SESSION['lang'] = $lang; 
    return $lang;
}



/********************************************************************************
Return the html template after replacing <t>variables</t> with the requested 
language (or with the fallback language as required)

$html    String    html template with variables of the form <t>name</t>
$lang    String    language to replace <t>variables</t> with
$fb        String    fallback language for when a translation is missing    
********************************************************************************/
function translate($html, $lang, $fb='en'){
    $strings = $GLOBALS[$lang];
    $fallback = $GLOBALS[$fb];
    $pattern = '!(?s)\<t\>([^\<]+)\<\/t\>!';
    $tr = function($match) use ($strings, $fallback){
        $key = $match[1];
        $st = $strings[$key];
        if(isset($strings[$key])){
            return $strings[$key];
        } else if (isset($fallback[$key])){
            logMsg("translation missing for $key");
            return $fallback[$key];
        } else {
            logMsg("translation missing for en $key");
            return "<t>$key</t>";
        }
    };
    return  preg_replace_callback($pattern, $tr, $html);
}


/********************************************************************************
Return the html template after replacing <v>variables</v> with the values provided.

$html        String        html template with variables of the form <v>key</v>
$variables    ArrayMap    variable name => $value
********************************************************************************/
function populate($html, $variables){
    $pattern = '!(?s)\<v\>([^\<]+)\<\/v\>!';
    $tr = function($match) use (&$variables){
        $m = $match[1];
        return isset($variables[$m]) ? $variables[$m] : "<v>$m</v>";
    };
    return  preg_replace_callback($pattern, $tr, $html);
}


/********************************************************************************
Return the html template after replicating <r>elements</r> with data from $player & $req.

$html    String        html template with variables of the form <v>key</v>
$player    XML            player data
$req    ArrayMap    url parameters from post&get
********************************************************************************/
function replicate($html, $player, $req){
    $tr = function($match) use ($player, $req){
        $id = $match[3];
        $html = $match[4];
        switch ($id){
            case 'repost':        return replicatePost($html, $req);                break;
            case 'language':    return replicateLanguages($html);                break;
            case 'games':        return replicateGames($html, $req);                break;
            case 'venues':        return replicateVenues($html);                    break;
            case 'similarVenues': return replicateSimilarVenues($html, $req);    break;
            case 'keen':
            case 'invitation':
            case 'accepted':
            case 'confirmed':
            case 'feedback':
            case 'cancelled':
            case 'history':        return replicateMatches($player, $html, $id);    break;
            case 'available':    return replicateAvailable($player, $html);        break;
            case 'rivalEmail':    return replicateEmailCheck($player, $html);        break;
            case 'familiar':    return replicateFamiliar($player, $html);        break;
            case 'ability':        return replicateAbility($player, $html);        break;
            case 'reckon':        return replicateReckons($player, $html);        break;
            case 'uploads':        return replicateUploads($player, $html);        break;
            default:            return '';
        }
    };
    $pattern = "!(?s)\<repeat((\sid='(.+?)')|[^\>]*)\>(.+?)\<\/repeat\>!";
    return  preg_replace_callback($pattern, $tr, $html);
}


$ICONS = array (
    'INFO_ICON' => $INFO_ICON,
    'TICK_ICON' => $TICK_ICON,
    'CROSS_ICON'=> $CROSS_ICON,
    'THUMB_UP_ICON' => $THUMB_UP_ICON,
    'THUMB_DN_ICON' => $THUMB_DN_ICON
);



function replicatePost($html, $req){
    $group = '';
    foreach($req as $name => $value){
        if(is_array($value)){
            $nam = "$name" . "[]";
            foreach($value as $val){
                 $vars = array(
                    'name'      => $nam,
                    'value'     => $val,
                );
                $group .= populate($html, $vars);
            }
        } else {
            $vars = array(
                'name'      => $name,
                'value'     => $value,
            );
            $group .= populate($html, $vars);
        }
    }    
    return $group;
}




function replicateGames($html, $req){
    global $games;
    $default = $req['game'];
    $group = '';
    foreach($games as $game => $name){
        $vars = array(
            'game'      => $game,
            'name'      => $name,
            'selected'  => ($game == $default ? 'selected' : '')
        );
        $group .= populate($html, $vars);
    }
    return $group;
}


function replicateVenues($html, $default){
    return "replicateVenues() has not been implemented";
echo "<br>REPLICATEVENUES<br>$html";
    $group = '';
    $venueIDs = listVenues('squash'); //$game);
    foreach($venueIDs as $vid => $playerCount){
echo "<br>$vid";
        $vars = array(
            'playerCount'   => $playerCount,
            'vid'              => $vid,
            'venueName'      => explode('|', $vid)[0]
        );
        $group .= populate($html, $vars);
    }
    return $group;
}



function replicateSimilarVenues($html, $req){
    $group = '';
    $vid = $req['vid'];
    $game = $req['game'];
//    $similar = similarVenues($req['venue'], $req['game']);
    $similar = array_slice(similarVenues($req['venue']), 0, 10);
    foreach($similar as $vid){
        $venue = readVenueXML($vid);
        $players = isset($venue) ? $venue->xpath("player") : array() ;
        $vars = array(
            'vid'        => $vid,
            'name'        => implode(', ',explode('|',$vid)),
            'players'    => count($players),
        );
        $group .= populate($html, $vars);
    }
    return $group;
}


function replicateMatches($player, $html, $status){
    global $ICONS;
    if(!$player){ return; }
    $group = '';
    $playerVars = playerVariables($player);
    foreach($player->matchStatus($status) as $matchXML) {
        $match = new Match($player, $matchXML);
        $matchVars = $match->variables();
        $vars = $playerVars + $matchVars + $ICONS;
        $vars['venueLink'] = venueLink($match->vid(), $player, $match->game());
        $group .= populate($html, $vars);
    }
    return $group;
}


function replicateAvailable($player, $html){
    global $ICONS, $games;
    if(!$player){ return; }
    $group = '';
    $playerVars = playerVariables($player);
    $available = $player->available();
    foreach($available as $avail){
        $game = $avail['game'];
        $availVars = array(
            'id'        => $avail['id'],
            'game'      => $games["$game"],
            'parity'    => $avail['parity'],
            'weekSpan'  => weekSpan($avail),
            'venueLink' => venueLink($avail->venue, $player, $game)
        );
        $vars = $playerVars + $availVars + $ICONS;
        $group .= populate($html, $vars);
    }
    return $group;
}


function replicateEmailCheck($player, $html){
    if(!$player){ return; }
    $group = '';
    $playerVars = playerVariables($player);
    $reckoning = $player->reckon("email");
    foreach($reckoning as $reckon){
        $game = $reckon['game'];
        $reckonVars = array('email' => $reckon['email']);
        $vars = $playerVars + $reckonVars ;
        $group .= populate($html, $vars);
    }
    return $group;
}


function replicateFamiliar($player, $html){
    global $ICONS, $games;
    if(!$player){ return; }
    $group = '';
    $playerVars = playerVariables($player);
    $reckoning = $player->reckon("rival");
    foreach($reckoning as $reckon){
        $game = $reckon['game'];
        $reckonVars = array(
            'id'        => $reckon['id'],
            'email'     => $reckon['email'],
            'game'      => $games["$game"],
            'parity'    => parityStr($reckon['parity'])
        );
        $vars = $playerVars + $reckonVars + $ICONS;
        $group .= populate($html, $vars);
    }
    return $group;
}


function replicateAbility($player, $html){
    global $games, $ICONS;
    if(!$player){ return; }
    $group = '';
    $abilities = array('<t>very_weak</t>', '<t>weak</t>', '<t>competent</t>', '<t>strong</t>', '<t>very_strong</t>');
    $playerVars = playerVariables($player);
    $reckoning = $player->reckon("region");
    foreach($reckoning as $reckon){
        $game = $reckon['game'];
        $ability = $reckon['ability'];
        $reckonVars = array(
            'id'        => $reckon['id'],
            'region'    => explode(',', $reckon['region'])[0],
            'game'      => $games["$game"],
            'ability'   => $abilities["$ability"]
        );
        $vars = $playerVars + $reckonVars + $ICONS;
        $group .= populate($html, $vars);
    }
    return $group;
}


function replicateLanguages($html){
    global $languages;
    $group = '';
    $current = $_SESSION['lang'];
    foreach($languages as $code => $lang){
        $vars = array(
            'code' => $code, 
            'language' => $lang,
            'selected' => $code == $current ? 'selected' : ''
        );
        $group .= populate($html, $vars);
    }
    return $group;
}



function replicateReckons($player, $html){
    if(!$player){ return; }
    $regions = regions($player);
    $group = '';
    foreach($regions as $region){
        $vars = array(
            'region' => $region,
        );
        $group .= populate($html, $vars);
    }
    return $group;
}



function replicateUploads($player, $html){
    if(!$player){ return; }
    $uploadIDs = $player->uploadIDs();
    $group = '';
    foreach($uploadIDs as $uploadID) {
        $ranking = $player->rankingGet($uploadID);
        $status = $ranking->status('active');
        $vars = array(
            'status'   => $status,
            'fileName' => $upload['fileName'],
            'crossAct' => $status == 'uploaded' ? 'delete' : 'deactivate',
            'tickIcon' => $status == 'uploaded' ? '<v>TICK_ICON</v>' : '',
            'title'    => $upload['title'],
            'game'     => $upload['game'],
            'time'     => $upload['time'] 
        );
        $group .= populate($html, $vars);
    }
    return $group;
}



    

/********************************************************************************
Returns a new DateTime object for the time string and time-zone requested

$str    String    time & date
$tz        String    time-zone
********************************************************************************/
function tzDateTime($str='now', $tz){
//echo "<br>VENUEDATETIME $str</br>" . $venue['tz'];
    if(empty($tz)){
        return new DateTime($str);
    }
    return new DateTime($str, timezone_open($tz));
}


/********************************************************************************
Returns a new DateTime object for a time at the $venue requested

$str    String    A time & date
$venue    XML        venue data
********************************************************************************/
function venueDateTime($str='now', $venue){
//echo "<br>VENUEDATETIME $str</br>" . $venue['tz'];
    return tzDateTime($str, $venue['tz']);
}



////////// VALIDATE ////////////////////////////////////////


# SECURITY escape all parameters to prevent malicious code insertion
# http://au.php.net/manual/en/function.htmlentities.php
function declaw($data){
    if (is_array($data)){
        foreach($data as $key => $val){
            $data[$key] = declaw($val);
        }
    } else {
        $data = htmlentities(trim($data), ENT_QUOTES | ENT_HTML5, "UTF-8");
    }
    return $data;
}


# SECURITY escape all parameters to prevent malicious code insertion
# http://au.php.net/manual/en/function.htmlentities.php
function reclaw($data){
    if (is_array($data)){
        foreach($data as $key => $val){
            $data[$key] = reclaw($val);
        }
    } else {
        $data = html_entity_decode($data, ENT_QUOTES | ENT_HTML5);
    }
    return $data;
}


/********************************************************************************
Return the $data string with all but a small set of safe characters removed

$data    String    An arbitrary string

Safe character set:
    abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789|:@ _-,./#
    
********************************************************************************/
function scrub($data){
    if (is_array($data)){
        foreach($data as $key => $val){
            $data[$key] = clip($key, scrub($val));
        }
    } else {
        $data = preg_replace("/[^(a-zA-Z0-9|:@ \_\-\,\.\/\#]*/", '', $data);
    }
    return $data;
}


/********************************************************************************
Return the $req data iff ALL variables are valid, or FALSE otherwise

$req    ArrayMap    url parameters from post&get
********************************************************************************/
function validate($req){
//echo "<br>VALIDATE<br>";
//error_reporting(E_ALL | E_STRICT);

    if(count($req) == 0){
        return FALSE;
    }

    $req = scrub($req);        // remove all but a small set of safe characters.

    $ability_opt = array('min_range' => 0, 'max_range' => 4);
    $parity_opt = array('min_range' => -2, 'max_range' => 2);
    $rep_opt = array('min_range' => -1, 'max_range' => 1);
    $hrs_opt = array('min_range' => 0, 'max_range' => 16777215);

    $args = array(
        'smtwtfs'    => array('filter' => FILTER_VALIDATE_INT,    'options' => $hrs_opt),
        'address'    => FILTER_DEFAULT,
        'ability'    => array('filter' => FILTER_VALIDATE_INT,    'options' => $ability_opt),
        'account'    => FILTER_DEFAULT,
        'country'    => array('filter' => FILTER_CALLBACK,    'options' => 'validateCountry'),
        'email'        => FILTER_VALIDATE_EMAIL,
        'Fri'        => array('filter' => FILTER_VALIDATE_INT,    'options' => $hrs_opt),
        'filename'    => FILTER_DEFAULT,
        'game'        => array('filter' => FILTER_CALLBACK,        'options' => 'validateGame'),
        'id'        => array('filter' => FILTER_CALLBACK,        'options' => 'validateID'),
        'invite'    => array('filter' => FILTER_CALLBACK,        'options' => 'validataInvite'),
        'Mon'        => array('filter' => FILTER_VALIDATE_INT,    'options' => $hrs_opt),
        'msg'        => FILTER_DEFAULT,
        'name'        => FILTER_DEFAULT,
        'nickname'  => FILTER_DEFAULT,
        'parity'    => array('filter' => FILTER_CALLBACK,        'options' => 'validateParity'),
        'phone'     => array('filter' => FILTER_CALLBACK,        'options' => 'validatePhone'),
        'pid'        => array('filter' => FILTER_CALLBACK,        'options' => 'validatePID'),
        'qwik'      => array('filter' => FILTER_CALLBACK,        'options' => 'validateQwik'),
        'Sat'       => array('filter' => FILTER_VALIDATE_INT,    'options' => $hrs_opt),
        'state'     => FILTER_DEFAULT,
        'suburb'    => FILTER_DEFAULT,
        'Sun'       => array('filter' => FILTER_VALIDATE_INT,    'options' => $hrs_opt),
        'Thu'        => array('filter' => FILTER_VALIDATE_INT,    'options' => $hrs_opt),
        'time'        => FILTER_DEFAULT,
        'today'        => array('filter' => FILTER_VALIDATE_INT,    'options' => $hrs_opt),
        'token'        => array('filter' => FILTER_CALLBACK,        'options' => 'validateToken'),
        'tomorrow'    => array('filter' => FILTER_VALIDATE_INT,    'options' => $hrs_opt),
        'Tue'        => array('filter' => FILTER_VALIDATE_INT,    'options' => $hrs_opt),
        'tz'        => FILTER_DEFAULT,
        'region'    => FILTER_DEFAULT,
        'rep'        => array('filter' => FILTER_VALIDATE_INT,    'options' => $rep_opt),
        'repost'    => array('filter' => FILTER_CALLBACK,        'options' => 'validateRepost'),
        'rival'        => FILTER_VALIDATE_EMAIL,
        'title'        => FILTER_DEFAULT,
//        'url'        => FILTER_VALIDATE_URL,
        'url'        => FILTER_DEFAULT,
        'venue'        => FILTER_DEFAULT,
        'Wed'       => array('filter' => FILTER_VALIDATE_INT,    'options' => $hrs_opt)
    );
    
    $result = filter_var_array($req, $args);

    if(in_array(FALSE, $result, TRUE)){
        echo "<br>";
        var_dump($result);
        return FALSE;
    }
    return $req;
//    return declaw($req);
}


function validateGame($val){
    global $games;
    return array_key_exists($val, $games) ? $val : FALSE;
}


function validateCountry($val){
    global $countries;
    return array_key_exists($val, $countries) ? $val : FALSE;
}


function validateID($val){ 
    return strlen($val) == 6 ? $val : FALSE;
}

function validateInvite($val){
    if (is_array($val)){
        return true;    // *********************** more validation required **************        
    }
    return false;
}


function validateParity($val){
    global $parityFilter;
    return in_array($val, $parityFilter) ? $val : FALSE;
}


function validatePID($val){
    return strlen($val) == 64 ? $val : FALSE;
}


function validatePhone($val){
    return strlen($val) <= 10 ? $val : FALSE;
}


function validateRepost($val){
    return $val;
}


function validateQwik($val){
    global $qwiks;
    return in_array($val, $qwiks) ? $val : FALSE;
}

function validateToken($val){
    return strlen($val) == 10 ? $val : FALSE;
}





/********************************************************************************
Post an explanation of a failed post&get request to error.php

$req    ArrayMap    url parameters from post&get
$msg    String        An explanatory message to display to the user at error.php
********************************************************************************/
function invalidRequest($post, $get, $msg){
    $str = '<b>POST</b><br>';
    foreach($post as $key => $val){
        $str .= "$key => $val<br>";
    }
    $str .= '<b>GET</b><br>';
    foreach($get as $key => $val){
        $str .= "$key => $val<br>";
    }
    header("Location: error.php?msg=<u>$msg</u><br>$str");
}


// An array of maximum string lengths.
// Used by: clip()
$clip = array(
    'address'        => 200,
    'description'    => 200,
    'filename'         => 50,
    'nickname'        => 20,
    'note'            => 2000,
    'region'        => 50,
    'state'            => 50,
    'suburb'        => 50,
    'tz'            => 100,
    'venue'         => 150
);

/********************************************************************************
Returns $val truncated to a maximum length specified in the global $clip array

$key    String    the $key of the global $clip array specifying the truncated length
$val    String    A string to be truncated according to global $clip array
********************************************************************************/
function clip($key, $val){
    global $clip;
    return array_key_exists($key, $clip) ? substr($val, 0, $clip[$key]) : $val ;
}



function subID($id){
    return (string)$id;
    return substr("$id",0, 10);
}




////////////////////// TIME //////////////////////////////

// handy bitfield constants
$Hrs24 = 33554431;
$Hrs6amto8pm = 16777215;


function hour2bit($hour){
    if ($hour < 0 || $hour >= 24) {
        return 0;
    }
    return 2 ** $hour;
}

function bit2hour(){
    $mask = 1;
    for ($hour = 0; $hour < 24; $hour++){
        if ($bits & $mask){
            return $hour;
        }
        $mask = $mask * 2;
    }    
}

/*******************************************************************************

*******************************************************************************/
function hours2bits($request, $day){
    $bitfield = 0;
    for ($hour = 0; $hour < 24; $hour++){
        $name = "$day$hour";
        if(isset($request[$name])){
            $bitfield += $request[$name];
        }
    } 
    return $bitfield;
}


// Accepts a bitfield representing the 24hrs in a day and returns and array of hours
function hours($bits){
    $hours = array();
    $mask = 1;
    for ($hour = 0; $hour < 24; $hour++){
        if ($bits & $mask){
            $hours[] = $hour ;
        }
        $mask = $mask * 2;
    }    
    return $hours;
}



function addHoursXML($element, $request, $day){
    $hourBits = hours2bits($request, $day);
    if ($hourBits > 0){
        $element->addChild($day, hours2bits($request,$day));
        return True;
    }
    return FALSE;
}


function day($tz, $dateStr){
    $date = tzDateTime($dateStr, $tz);
    $today = tzDateTime('today', $tz);
    $interval = $today->diff($date);
    switch ($interval->days) {
        case 0: return 'today'; break;
        case 1: return $interval->invert ? 'yesterday' : 'tomorrow'; break;
        default:
            return $date->format('jS M');
    }
}




function hr($hr){
    global $clock24hr;
    $apm = ':00';
    if (!$clock24hr){
        if ($hr < 12){
            $apm = 'am';
        } elseif ($hr > 12) {
            $hr = $hr - 12;
            $apm = 'pm';
        } else {
            $apm = 'pm';
        }
    }
    return "$hr$apm";
}


/*******************************************************************************
Returns the sha256 hash of the $email address provided

$email    String    an email address

The unique player ID is chosen by taking the sha256 hash of the email address. 
This has a number of advantages:
- The player ID will be unique because the email address will be unique
- Qwikgame can accept and use a sha256 hash to store anonymous player data
- A new email address can be linked to existing anonymous player data

*******************************************************************************/
function anonID($email){
    return hash('sha256', $email);
}


function snip($str){
    return substr($str, 0, 4);
}



/*******************************************************************************
Returns the sha256 hash of the $token provided

$token    String    an token

When it is necessary to send a token to a user (e.g. via email as a proof of 
identity) then only the sha256 hash of the token is stored by qwikgame.
This has a number of advantages:
- the sha256 hash can be computed on presented tokens and validated against the 
stored hash
- if the system is compromised then the user held token remain secure.
*******************************************************************************/
function nekot($token){
    return hash('sha256', $token);
}


function venueID($name, $address, $suburb, $state, $country){
    return "$name|$address|$suburb|$state|$country";
}



function updateVenueID(&$venue){
// echo "<br>UPDATEVENUEID<br>";
    if(empty($venue)){
        return false;
    }

    $vid = venueID(
        $venue['name'], 
        $venue['address'], 
        $venue['suburb'], 
        $venue['state'], 
        $venue['country']
    );
    renameVenue($venue, $vid);
}


// may introduce inconsistent results under hi multi-user load

function renameVenue(&$venue, $newID){
// echo "<br>RENAMEVENUE to $newID<br>";
    $preID = (string) $venue['id'];
    if($newID != $preID){
        $venue['id'] = $newID;
        writeVenueXML($venue);

        // temporarily replace oldfile with a symlink
        deleteFile("venue/$preID.xml");
        symlink("venue/$newID.xml", "venue/$preID.xml");

        $pids = $venue->xpath('player');
        foreach($pids as $pid){
            $player = new Player($pid, $log);
            $player->venueRename($preID, $newID);
            $player->save();
        }

        $games = $venue->xpath('game');
        foreach($games as $game){
            symlink("venue/$newID.xml", "venue/$game/$newID.xml");
            deleteFile("venue/$game/$preID.xml");
        }
        deleteFile("venue/$preID.xml");    // delete temp symlink
    }
}



function newVenue($description){

        $field = explode(',', $description);
        $record = "<venue ";

        if(count($field) > 0){
                $f = trim(array_shift($field));
                $record .= " name='$f'";
        }

        if(count($field) > 0){
                $f = trim(array_shift($field));
                $record .= " address='$f'";
        }

        if(count($field) > 0){
                $f = trim(array_shift($field));
                $record .= " suburb='$f'";
        }

        if(count($field) > 0){
                $f = trim(array_shift($field));
                $record .= " state='$f'";
        }

        if(count($field) > 0){
                $f = trim(array_shift($field));
                $record .= " country='$f'";
        }

        $record .= " />";

        $venue = new simplexmlelement($record);
        return $venue;
}



function updateVenueAtt(&$venue, $key, $update){
//echo "<br>UPDATEVENUEATT $key ";
    if (isset($update[$key])){
        $newVal = $update[$key];
        $datetime = venueDateTime('now', $venue);
        $date = $datetime->format('d-m-y H:i');
        $oldVal = $venue[$key];
        if ($oldVal != $newVal){
            if ( strlen(trim($oldVal)) > 0){
                $edit = $venue->addChild('edit', '');
                $edit->addAttribute('date', $date);
                $edit->addAttribute('id', newID());
                $edit->addChild('key', $key);
                $edit->addChild('val', $oldVal);                
            }
            $venue[$key] = $newVal;    
            return true;
        }
    }
    return false;
}


function updateVenueArray($venue, $key, $update){

}


function updateVenue(&$venue, $update){
//echo "<br>UPDATEVENUE<br>";
    $save = updateVenueAtt($venue, 'name', $update);
    $save = updateVenueAtt($venue, 'address', $update) || $save;
    $save = updateVenueAtt($venue, 'suburb', $update) || $save;
    $save = updateVenueAtt($venue, 'state', $update) || $save;
    $save = updateVenueAtt($venue, 'country', $update) || $save;
    if($save){
        updateVenueID($venue);
    }
    $save = updateVenueAtt($venue, 'phone', $update) || $save;
    $save = updateVenueAtt($venue, 'url', $update)  || $save;
    $save = updateVenueAtt($venue, 'tz', $update) || $save;
    $save = updateVenueAtt($venue, 'note', $update) || $save;
    $save = updateVenueAtt($venue, 'lat', $update) || $save;
    $save = updateVenueAtt($venue, 'lng', $update) || $save;
//    || updateVenueArray($venue, 'games', $update)
    if($save){
        writeVenueXML($venue);
    }
}

// https://secure.php.net/manual/en/class.simplexmlelement.php
// Must be tested with ===, as in if(isXML($xml) === true){}
// Returns the error message on improper XML
function isXML($xml){
    libxml_use_internal_errors(true);

    $doc = new DOMDocument('1.0', 'utf-8');
    $doc->loadXML($xml);

    $errors = libxml_get_errors();

    if(empty($errors)){
        return true;
    }

    $error = $errors[0];
    if($error->level < 3){
        return true;
    }

    $explodedxml = explode("r", $xml);
    $badxml = $explodedxml[($error->line)-1];

    $message = $error->message . ' at line ' . $error->line . '. Bad XML: ' . htmlentities($badxml);
    return $message;
}



function pickVenue($req, $sourceUrl){
    if(isset($req['venue'])){
        $venueID = $req['venue'];
        $venue = readVenueXML($venueID);
        if ($venue){
            return $venue;
        } else {
            $req['url'] = $sourceUrl;
            $data = array(
                'description' => $description,
                'game' => $req['game'],
                'repost' => $req
            );
            post("http://$subdomain.qwikgame.org/venue.php", $data);
        }
    }
}


function removePlayer($id){
//echo "REMOVEPLAYER $id<br>";
    $path = 'player';
    $filename = "$id.xml";
    return deleteFile("$path/$filename");
}


function writeVenueXML($venue){
//echo "<br>WRITEVENUEXML<br>";
    $cwd = getcwd();
    $vid = $venue['id'];
    $filename = "$vid.xml";
    writeXML("venue", $filename, $venue);
    if(chdir("venue")){
        $games = $venue->xpath('game');
        foreach($games as $game){
            if(!file_exists("$game/$filename")){
                if(file_exists($game) && chdir($game)){
                    symlink("../$filename", $filename);
                    chdir("..");
                } else {
                   logMsg("Unable to create symlink for $game/$filename");
                }
            }
        }
    }
    chdir($cwd);
}


function readVenueXML($vid){
    return readXML('venue', "$vid.xml");
}



function writeXML($path, $file, $xml){
    $cwd = getcwd();
    if(chdir($path)){
        $xml->asXML($file);
        if(chdir($cwd)){
            return false;
        }
    } else {
        return false;
    }
}


function readXML($path, $file){
    $path = reclaw($path);
    $file = reclaw($file);
    
    if (!file_exists("$path/$file")) {
        return FALSE;
    }
    
    $cwd = getcwd();
    if(chdir($path)){
        $xml=simpleXML_load_file($file);
        if(!chdir($cwd)){
            echo "readxml() error changing to cwd";
        }
        return $xml;
    }
    return;
}


function countFiles($path){
    return iterator_count(new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS));
}


function deleteFile($file){
//echo "<br>DELETEFILE $file<br>";
//    $fileName = realpath("$file");

    if (is_writable($file)){
//echo "<br>$file is writeable<br>";
        return unlink($file);
    }
    return false;
}




function lockXML($xml, $token){
    $nekot = hash('sha256', $token);
    if (isset($token)){
        if (isset($xml['lock'])){
            $xml['lock'] = $token;
        } else {
            $xml->addAttribute('lock', $token);
        }
    }
}



function unlockXML($xml, $token){
    $nekot = hash('sha256', $token);
    $locked = $xml->xpath("//*[@lock='$token']");
    foreach($locked as $open){
        removeAtt($open, 'lock');
    }
}


function isLocked($xml){
//    return ! empty($xml['lock']);
    return isset($xml['lock']) && strlen($xml['lock']) > 0;
}


// https://stackoverflow.com/questions/720751/how-to-read-a-list-of-files-from-a-folder-using-php
function fileList($dir){
    $fileList = array();
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                $fileList[] = $file;
            }
            closedir($dh);
        }
    }
    return $fileList;
}


function venues($game){
    $venues = array();
    $path = "venue";
    $path .= $game ? "/$game" : '';
    $fileList = fileList($path);
    foreach($fileList as $file){
        if (substr_count($file, '.xml') > 0){
            $venues[] = str_replace('.xml', '', $file);
        }
    }
    return $venues;
}


function pids($game){
    $pids = array();
    $fileList = fileList("player");
    foreach($fileList as $file){
        if (substr_count($file, '.xml') > 0){
            $pids[] = str_replace('.xml', '', $file);
        }
    }
    return $pids;
}


function trim_value(&$value) 
{ 
    $value = trim($value); 
}


function similarVenues($description, $game){
    $similar = array();
    $existingVenues = venues($game);
    $words = explode(" ", $description, 5);
    array_walk($words, 'trim_value'); 
    
    foreach($existingVenues as $venueID){
        $venueid = strtolower($venueID);
        $hits = 0;
        foreach($words as $word){
            $hits += substr_count($venueid, strtolower($word));
        }
        if($hits > 0){
            $similar[$venueID] = $hits;
        }

    }
    arsort($similar);
    return array_keys($similar);
}


function listVenues($game){
//echo "<br>LISTVENUES<br>";
    $venues = venues($game);
    $sorted = array();
    foreach($venues as $vid){
        $venue = readVenueXML($vid);
        if(isset($venue)){
            $players = $venue->xpath('player');
            $sorted[$vid] = count($players);
        }
    }
    arsort($sorted);
    return $sorted;
}


function venueList($game){
    $venueIDs = listVenues($game);
    $list = "<ul class='sorted'>";
    foreach($venueIDs as $vid => $n){
        $name = explode('|', $vid)[0];
        $list .= "<li><b>$n</b> <a href='venue.php?game=$game&vid=$vid'>$name</a></li>";
    }
    $list .= '</ul>';
    return $list;
}


function repostInputs($repost, $tabs){
//echo "<br>REPOSTINPUTS<br>";
    $inputs = '';
    foreach($repost as $key => $val){
        $val = reclaw($val);
        $inputs .= "$tabs<input type='hidden' name='repost[$key]' value='$val'>\n";
    }
    return $inputs;
}




function getPlayers($venue){
//echo "GETPLAYERS<br>";
    $players = array();
    $pids = $venue->xpath('player');
    foreach($pids as $pid){                         // all available players at venue
        $player = new Player($pid, $log);
        if ($player){
            $players[] = $player;
        } else {    // opportunitic maintainence
            $awol = $venue->xpath("player=$pid");
            removeElement($awol);
        }
    }
    return $players;
}


function getCandidates($player, $venue, $matchHours){
//echo "SENDINVITES<br>";
    $candidates = array();
    $playerID = $player->id();
    $rivalIDs = $venue->xpath('player');
    unset($rivalIDs[array_search($playerID, $rivalIDs)]);    // exclude self
    foreach($rivalIDs as $rivalID){                            // all available rivals at venue
        $rival = new Player($rivalID, $log);
        if ($rival){
            $availableHours = $rival->availableHours($player, $match);
            $keenHours = $rival->keenHours($player, $match);
            $inviteHours = $matchHours & ($availableHours | $keenHours);
            if ($inviteHours > 0){
                $candidates[$rival] = $inviteHours;
            }
        } else {    // opportunitic maintainence
            $awol = $venue->xpath("player=$rivalID");
            removeElement($awol);
        }
    }
    return $candidates;
}




//scans and removes edits over 1 week old
function concludeReverts($venue){
    return;

    $edits = $venue->xpath('edit');
    foreach($edits as $edit){
        $date = venueDateTime($edit->date['date'], $venue);
        if ($date > strtotime('+1 week')){
            removeElement($edit);
        }
    }
    writeVenueXML($venue);
}


// https://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425 
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}


function newID($len = 6){
    return generateRandomString($len);
}


function newToken($len = 10){
    return generateRandomString($len);
}


function removeElement($xml){
    $dom=dom_import_simpleXML($xml);
    $dom->parentNode->removeChild($dom);
}

function removeAtt($xml, $att){
    $dom=dom_import_simpleXML($xml);
    $dom->removeAttribute($att);
}




////// QWIK SWITCH ///////////////////////////////////////////////////////////



function qwikAvailable($player, $request, $venue){
    if(isset($request['game']) & isset($request['vid']) & isset($request['parity'])){
        $newID = $player->availableAdd(
            $request['game'], 
            $request['vid'], 
            $request['parity'],
            $venue['tz'],
            isset($request['smtwtfs']) ? $request['smtwtfs'] : FALSE,
            $request
        );
        venuePlayer($venue, $player->id());
        return $newID;
    }
}


function qwikKeen($player, $req, $venue){
//echo "<br>QWIKKEEN<br>";
    if(empty($req)
    || empty($venue)
    || empty($req['game']) 
    || (empty($req['today']) && empty($req['tomorrow']))){
        logMsg("qwikKeen() missing required arguments");
        return;
    }

    $game = $req['game'];

    // build an array of Familiar Rivals to invite
    $pid = $this->id();
    $familiarRids = array();
    if (isset($req['invite'])){
        $emails = $req['invite'];
        if (is_array($emails)){
            foreach($emails as $email){
                $familiarRids[] = anonID($email);
            }
        }
    }
    unset($familiarRids[$pid]);    // exclude self;


    // build an array of other available Rivals to invite
    $rids = $req['invite-available'] 
        ? array_diff(
            array_map(
                function($obj){return "$obj";},
                $venue->xpath('player')
            ),
            $familiarRids,             // exclude explicit invitations
            array($pid)                // exclude self
        )
        : array();


    $days = array('today','tomorrow');
    foreach($days as $day){
        $date = venueDateTime($day, $venue);
        $hours = (int) $req[$day];
        if ($hours > 0){
             $match = $player->matchKeen($game, $venue, $date, $hours);
             $match->invite($familiarRids, TRUE);
             $match->invite($anonRids);
             $match->save();
        }
    }
}


function qwikDecline($player, $request){
//echo "<br>QWIKDCLINE<br>";
    $playerID = $player->id();
    if(isset($request['id'])){
        $player->matchDecline($request['id']);
    }
}


function venuePlayer($venue, $playerID){
    if (count($venue->xpath("/venue[player='$playerID']")) == 0){
        $venue->addChild('player', "$playerID");
        writeVenueXML($venue);
    }
}


function venueAddGame($venue, $game){
//echo "<br>VENUEADDGAME $game<br>";
    if(count($venue->xpath("/venue[game='$game']")) == 0){
        $venue->addChild('game', $game);
        return true;
    }
    return false;
}


function venueRemoveGame($venue, $game){
    $elements = $venue->xpath("/venue[game='$game']");
//echo "aborted request to delete $game";

    foreach($elements as $element){    
//print_r($element);
//echo "<br><br>";
//        removeElement($element);
    }
    $vid = $venue['id'];
//    deleteFile("venue/$game/$vid.xml");
}


function qwikAccept($player, $request){
//echo "<br>QWIKACCEPT<br>";
//echo "request = ";
//print_r($request);
//echo "<br><br>";

//echo "player = ";
//print_r($player);
//echo "<br><br>";

    if(isset($request['id']) & isset($request['hour'])){
        $matchID = $request['id'];
        $match = $player->matchID($matchID);
        if (!isset($match)){
            header("Location: error.php?msg=unable to locate match.");
            return;
        }
//echo "match = ";
//print_r($match);
//echo "<br><br>";

        $match->accept($request['hour']);
        $player->save();
    }
}


function qwikFeedback($player, $request){
    if(isset($request['id']) & isset($request['rep']) & isset($request['parity'])){
        $rival = $player->outcomeAdd(
            $request['id'], 
            $request['parity'], 
            $request['rep']
        );

        if ($rival->exists()){
            $rival->updateRep($request['rep']);
            updateCongCert($player, $request['id'], $rival);
            $rival->save();
        }
    } else {
        header("Location: error.php?msg=malformed feedback.");
    }
}


function qwikMsg($player, $req){
    if(isset($req['id']) & isset($req['msg'])){
        $player->matchMsg($req['id'], $req['msg']);
    }
}


/*******************************************************************************

qwikgame attempts to estimate the PARITY of possible RIVALs prior to each MATCH.

After each MATCH both PLAYERSs rate the PARITY of their RIVAL's ability:
    +2  much stronger
    +1  stronger
     0  well matched
    -1  weaker
    -2  much weaker
There may be DISPARITY between the two ratings. 

A player's RELYability measures their consistency in rating their RIVALs.
There can be DISPARITY between two Players rating of each other. DISPARITY causes
a Players RELYability to drop, but the PLayer with the lower historical RELYability
will suffer most from any DISPARITY (on the assumption that they are the probable
cause)

The CONFIDENCE in each PARITY rating is used to resolve DISPARITY during estimates.
Each PARITY rating has a CONFIDENCE which is high when two rivals with 
high CONGRUENCE rate each other with no DISPARITY (and vice versa).


// refine CONGRUENCE and CERTAINTY when RIVAL Feedback also exists
********************************************************************************/

function updateCongCert($player, $matchID, $rival){
    $pOutcome = $player->outcome($matchID);
    $rOutcome = $rival->outcome($matchID);

    if (null !== $pOutcome && null !== $rOutcome){

        $pParity = intval($pOutcome['parity']);
        $rParity = intval($rOutcome['parity']);

        $pRely = $player->rely();
        $rRely = $rival->rely();

        $disparity = abs($rParity + $pParity);    // note '+' sign & range [0,4]
        $player->rely(($disparity * $rRely * $rRely) / 16);
        $rival->rely(($disparity * $pRely * $pRely) / 16);

        $congruence = 4 - $disparity;             // range [0,4]
        $player->outcome($matchID, ($pRely * $congruence) / 4);
        $rival->outcome($matchID, ($rRely * $congruence) / 4);
    }
}




function qwikFamiliar($player, $request){
    if(isset($request['game'])
    && isset($request['rival'])
    && isset($request['parity'])){
        $player->familiar($request['game'], $request['rival'], $request['parity'], $log);
    }
}


function qwikRegion($player, $request){
    if(isset($request['game'])
        && isset($request['ability']) 
        && isset($request['region'])){
            $player->region($request['game'], $request['ability'], $request['region']);
    }
}


function qwikAccount($player, $request){
//echo "<br>QWIKACCOUNT<br>";
    global $qwikURL, $DAY;
    if(isset($request['nick'])){
        $player->nick($request['nick']);
    }

    if(isset($request['url'])){
        $player->url($request['url']);
    } 

    if(isset($request['email'])){
        $email = $request['email'];
        if ($email != $player->email()){
            emailChange($email, $player->id(), $player->token(Player::DAY));
        }
    }

    if(isset($request['lang'])){
        $player->lang($request['lang']);
    } 

    if(isset($request['account']) && ($request['account'] === 'quit')) {
        $player->emailQuit();
        $player->quit();
        logout();
    
        header("Location: $qwikURL");
    }    
}


function qwikCancel($player, $req){
    if(isset($req['id'])){
        $player->matchCancel($req['id']);
    }
}



function qwikDelete($player, $request){
    $player->delete($request['id']);
}


function qwikContact($msg, $from){
    $headers = array();
    $headers[] = "From: $from";
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/html; charset=UTF-8";

//    $to = 'facilitator@qwikgame.org';
    $to = 'john@nhoj.info';
    $subject = "feedback from $from";

    if (! mail($to, $subject, $msg, implode("\r\n", $headers))){
        header("Location: error.php?msg=<b>The email was unable to be sent");
    }
}




////// EMAIL TEMPLATES ///////////////////////////////////////////////////////

function authURL($email, $token) {
    global $qwikURL;
    
    return "$qwikURL/player.php?qwik=login&email=$email&token=$token";
}


function emailWelcome($email, $id, $token){
    global $qwikURL, $termsURL;

    $authURL = authURL($email, $token);

    $subject = "Welcome to qwikgame.org";

    $msg  = "<p>\n";
    $msg .= "\tPlease click this link to <b>activate</b> your qwikgame account:<br>\n";
    $msg .= "\t<a href='$authURL' target='_blank'>$authURL</a>\n";
    $msg .= "\t\t\t</p>\n";
    $msg .= "\t\t\t<p>\n";
    $msg .= "\t\t\tBy clicking on these links you are agreeing to be bound by these \n";
    $msg .= "\t\t\t<a href='$termsURL' target='_blank'>\n";
    $msg .= "\t\t\tTerms & Conditions</a>";
    $msg .= "</p>\n";
    $msg .= "<p>\n";
    $msg .= "\tIf you did not expect to receive this request, then you can safely ignore and delete this email.\n";
    $msg .= "<p>\n";

    qwikEmail($email, $subject, $msg, $id, $token);
    logEmail('welcome', $id);
}



function emailLogin($email, $id, $token){
    global $qwikURL;

    $authURL = authURL($email, $token);

    $subject = 'qwikgame.org login link';

    $msg  = "<p>\n";
    $msg .= "\tPlease click this link to login and Bookmark for easy access:<br>\n";
    $msg .= "\t<a href='$authURL' target='_blank'>$authURL</a>\n";
    $msg .= "\t\t\t</p>\n";
    $msg .= "<p>\n";
    $msg .= "\tIf you did not expect to receive this request, then you can safely ignore and delete this email.\n";
    $msg .= "<p>\n";

    qwikEmail($email, $subject, $msg, $id, $token);
    logEmail('login', $id);
}


function emailStash($email, $page, $req, $id, $token){
    global $qwikURL;

    $subject = 'qwikgame.org confirm availability';
    $query =  http_build_query($req);
    $game = $req['game'];
    $venue = $req['venue'];

    $msg  = "<p>\n";
    $msg .= "\tPlease click this link to \n";
    $msg .= "\t<a href='$qwikURL/$page?$query' target='_blank'>confirm</a>\n";
    $msg .= " that you are available to play <b>$game</b> at <b>$venue</b>.<br>\n";
    $msg .= "\t\t\t</p>\n";
    $msg .= "<p>\n";
    $msg .= "\tIf you did not expect to receive this request, then you can safely ignore and delete this email.\n";
    $msg .= "<p>\n";

    qwikEmail($email, $subject, $msg, $id, $token);
    logEmail('login', $id);
}


function emailChange($email, $id, $token){
    global $qwikURL;

    $subject = 'Confirm email change for qwikgame.org';

    $msg  = "<p>\n";
    $msg .= "\tPlease click this link to change your qwikgame email address to $email:<br>\n";
    $msg .= "\t<a href='$qwikURL/player.php?qwik=login&pid=$id&email=$email&token=$token' target='_blank'>$qwikURL/player.php?qwik=login&pid=$id&email=$email&token=$token</a>\n";
    $msg .= "\t\t\t</p>\n";
    $msg .= "<p>\n";
    $msg .= "\tIf you did not expect to receive this request, then you can safely ignore and delete this email.\n";
    $msg .= "<p>\n";

    qwikEmail($email, $subject, $msg, $id, $token);
    logEmail('email', $id);
}





function qwikEmail($to, $subject, $msg, $id, $token){

    global $qwikURL, $termsURL;

    $headers = array();
    $headers[] = "From: facilitator@qwikgame.org";
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/html; charset=UTF-8";

    $body  = "<html>\n";
    $body .= "\t<head>\n";
    $body .= "\t\t<meta charset='UTF-8'>\n";
    $body .= "\t\t<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
    $body .= "\t\t<link href='https://fonts.googleapis.com/css?family=Pontano+Sans' rel='stylesheet' type='text/css'>";
    $body .= "\t\t<link rel='stylesheet' type='text/css' href='qwik.css'>";
    $body .= "\t\t<script src='https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js'></script>";
    $body .= "\t</head>\n";
    $body .= "\t<body>\n";

    $body .= "$msg";

    $body .= "\t\t<br><hr>\n";
    $body .= "\t\t<p>\n";
    $body .= "\t\t\tBy clicking on these links you are agreeing to be bound by these \n";
    $body .= "\t\t\t<a href='$termsURL' target='_blank'>\n";
    $body .= "\t\t\tTerms & Conditions</a>";
    $body .= "\t\t</p>\n";
    $body .= "\t\t</p>\n";
    $body .= "\t\t\tFind someone to play your favourite game at a time and place that suits you.\n";
    $body .= "\t\t</p>\n";
    $body .= "\t</body>\n";
    $body .= "</html>\n";

    if (! mail($to, $subject, $body, implode("\r\n", $headers))){
        header("Location: error.php?msg=<b>The email was unable to be sent");
    }
}






////// HTML ELEMENTS //////////////////////////////////////////////////////////



function datalists(){
    global $games;
    $datalists = '';
    foreach($games as $game => $name){
        $datalists .= "\n\n" . venueDatalist($game);
    }
    return $datalists;
}


/*******************************************************************************
Returns the Name & Suburb of a Venue as a human convenient reference.

$vid    String    Venue ID
*******************************************************************************/
function shortVenueID($vid){
    $address = explode('|', $vid);
    return $address[0] . ' | ' . $address[2];
}


function venueDatalist($game){
    $vids =venues($game);
    $datalist = "<datalist id='venue-$game'>\n";
    foreach($vids as $vid){
        $svid = shortVenueID($vid);
        $datalist .= "\t<option value='$svid'>\n";
    }
    $datalist .= "</datalist>\n";
    return $datalist;
}


/*******************************************************************************
Returns an Array of Venue ID's (vid) that match the $svid provided.

$svid    String    The Short Venue ID includes only the Name & Suburb of the Venue.

The Short Venue ID $svid is a non-unique human convenient way of referring to a
Venue. This functions finds zero or more $vid that match the $svid
*******************************************************************************/
function matchShortVenueID($svid, $game){
    $match = array();
    $vids =venues(strtolower($game));
    foreach($vids as $vid){
        if($svid == shortVenueID($vid)){
            $match[] = $vid;
        }
    }
    return $match;
}


function daySpan($bits, $day=''){
    global $clock24hr;
    $hours = hours($bits);
    if (count($hours) > 0){
        $dayX = substr($day, 0, 3);
        $dayP = $clock24hr ? 0 : 12;

        if (count($hours) == 24){
            return "<span class='lolite'><b>$dayX</b></span>";
        } else {
            $str =  $clock24hr ? $dayX : '';
            foreach($hours as $hr){
                $pm = $hr > 12;
                $consecutive = $hr == ($last + 1);
                $str .= $consecutive ? "&middot" : clock($last) . ' ';
    
                if ($pm && !$clock24hr) {
                    $str .= "<b>$dayX</b>";
                    $dayX = '';
                }

                $str .= $consecutive ? '' : " " . clock($hr);
                $last = $hr;
            }
            $str .= $consecutive ? clock($last) : '';
            return "<span class='lolite'>$str</span>";
        }
    }
    return "";
}


function clock($hr){
    global $clock24hr;
    return (($hr > 12) && !$clock24hr) ? $hr-12 : $hr;
}


function weekSpan($xml){
    $html = "";
    $hrs = $xml->xpath("hrs");
    foreach($hrs as $hr){
        $html .= daySpan($hr, $hr['day']);
    }

    return $html;
}


function hourSelect($hrs){
    global $clock24hr;
    if (count($hrs) == 1){
        $hr = $hrs[0];
        $hourbit = pow(2, $hr);
        $hour = hr($hr);
        $html = "$hour<input type='hidden' name='hour' value='$hourbit'>";
    } else {
        $html = "<select name='hour' required>\n";
        $html .= "<option selected disabled>time</option>";
        foreach ($hrs as $hr){
            $hourbit = pow(2, $hr);     // $hourbit =(2**$hr);    // php 5.6
            $hour = hr($hr);
            $html .= "\t<option value='$hourbit'>$hour</option>\n";
        }
        $html .= "</select>\n";
    }
    return $html;
}


function parityStr($parity){
//echo "<br>PARITYSTR $parity<br>";
    if(!is_numeric("$parity")){
        return '';
    }

    $pf = floatval($parity);
    if($pf <= -2){
        return "<t>much_weaker</t>";
    } elseif($pf <= -1){
        return "<t>weaker</t>";
    } elseif($pf < 1){
        return "<t>well_matched</t>";
    } elseif($pf < 2){
        return "<t>stronger</t>";
    } else {
        return "<t>much_stronger</t>";
    }
}


function keenTable($min, $max){

    $venue = readVenueXML($vid);
    $tz = empty($venue) ? local : $venue['tz'];

    $tds = '';
    $bit = 1;
    for($hr=0; $hr<24; $hr++){
        $hidden = ($hr<$min || $hr>$max) ? 'hidden' : '';
        $table .= "\t\t<td class='toggle' bit='$bit' $hidden>$hr</td>\n";
        $bit = $bit * 2;
    }
    return $tds;
}


function cmpMatch($matchA, $matchB){
    global $status;    
    $a = $matchA['status'];
    $b = $matchB['status'];
    if ($status["$a"] < $status["$b"]){
        return 1;
    } elseif ($status["$a"] > $status["$b"]){
        return -1;
    } else {
        return 0;
    }
}


function venueLink($vid, $player, $game){
    $name = explode('|', $vid)[0];
    $words = explode(' ', $name);
    $first = $words[0];
    $words[0] = "<b>$first</b>";
    $name = implode(' ', $words);
}




function repStr($player){
    $word = repWord($player);
    return empty($word) ? 'AAAAAA' : " with a $word reputation";
}


function repFraction($player){
    $rep = $player->rep();
    $repPos = intval($rep['pos']);
    $repNeg = intval($rep['neg']);
    $repTot = $repPos + $repNeg;
    $thumb = "<span class='fa fa-thumbs-o-up green'></span>";
    return "$repPos $thumb / $repTot";
}


function repThumbs($player){
    $rep = $player->rep();
    $repPos = intval($rep['pos']);
    $repNeg = intval($rep['neg']);
    $thumbUp = "<span class='fa fa-thumbs-o-up green'></span>";
    $thumbDown = "<span class='fa fa-thumbs-o-down red'></span>";
    return str_repeat($thumbDown, $repNeg) . str_repeat($thumbUp, $repPos);
}


function playerVariables($player){
    return array(
        'target'    => 'player.php#matches',
        'reputation'=> repStr($player)
    );
}



function familiarEmailLink($venue, $game, $name){
    $venueName = $venue['name'];
    $link = "<a id='email-share'
        href='mailto:?subject=$name is keen for $game at $venueName&target=_blank&
        body=www.qwikgame.org%20makes%20it%20easy%20to%20find%20someone%20to%20play%20your%20
        favourite%20game%20at%20a%20time%20and%20place%20that%20suits%20you.\n\n\n'>
        <img src='img/email.png' alt='email' class='socialmedia'></a>";
    return $link;
}


function venueRevertDiv($venue){
    global $revert;
    $edits = $venue->xpath('edit');
    if (count($edits) == 0){
        return '';
    }

    $div .= "<div id='edit-revert-div' class='middle'>\n";
    $div .= "\tClick to revert a prior edit.<br>\n";
    foreach($edits as $edit){
        $revertID = $edit['id'];
        $div .= "\t<button class='revert' id='#venue-$edit->key' val='$edit->val'>";
        $div .= "\t\t$revert <s>$edit->val</s>\n";
        $div .= "\t</button>\n";
    }
    $div .= "\t<br>\n";
    $div .= "</div>\n";
    return $div;
}


function venueEditForm($action, $venue, $game, $repost, $pid, $token){
//echo "<br>VENUEEDITFORM<br>";
    global $tick, $cross, $help;
    $repostInputs = repostIns($repost, "\t\t\t");
    $country = $venue['country'];
    $countryOptions = countryOptions($country, "\t\t\t\t");
    $vid = $venue['id'];
    $venueName = $venue['name'];
    $venueAddress = $venue['address'];
//   $venueSuburb = (empty($venue['suburb'])) ? geolocate('city') : $venue['suburb'];
    $venueSuburb = $venue['suburb'];
    $venueState = (empty($venue['state'])) ? geolocate('region') : $venue['state'];
    $venuePhone = $venue['phone'];
    $venueURL = $venue['url'];
    $venueTZ = $venue['tz'];
    $venueNote = $venue['note'];

    $form = "
    <form id='edit-venue-form' action='$action' method='post' class='commit center shadow venue'>
        <input type='hidden' name='pid' value='$pid'>
        <input type='hidden' name='token' value='$token'>
        <input type='hidden' name='vid' value='$vid'>
        $repostInputs
        <span class='help' hidden>
            Please correct any details for this Venue.<br>
            You can easily revert changes by clicking on the <s>struckout</s> data 
            from prior edits.
        </span>
        <button type='button' class='action help fa $help'></button>
        <p class='center'>
            <input id='venue-name' name='name' class='center' value='$venueName' placeholder='Name'>
            <input id='venue-address' name='address' class='center' value='$venueAddress' placeholder='Address'>
            <input id='venue-suburb' name='suburb' class='center' value='$venueSuburb' placeholder='Suburb'>
            <input id='venue-state' name='state' class='center' value='$venueState' placeholder='State / Provence'>
            <select id='venue-country' name='country' class='center' required>
                $countryOptions
            </select>
            <br><br>
            <input id='venue-phone' name='phone' class='center' placeholder='phone' value='$venuePhone'>
            <input id='venue-url' name='url' type='url' class='center' placeholder='http://' value='$venueURL'><br>
            <select id='tz' name='tz' class='center' required>
                <option disabled value>timezone</option>
                <option value='$venueTZ'>$venueTZ</option>
            </select>
            <br><br>
            <input id='venue-game' name='game' class='center' type='hidden' value='$game'>
            <textarea id='venue-note' name='note' class='center' placeholder='...notes...' >
                $venueNote
            </textarea>
        </p>
        <br>
        <input id='venue-cancel' type='button' value='Cancel'>
        <input id='venue-submit' type='submit' value='Submit'>
        <br><br>
    </form>\n";
    return $form;
}


function repostIns($repost, $tabs=''){
//echo "<br>REPOSTINPUTS<br>";
    $braces = '[]';
    $inputs = '';
    foreach($repost as $key => $val){
        if (is_array($val)){
            foreach($val as $v){
                $v = reclaw($v);
                $inputs .= "$tabs<input type='hidden' name='$key$braces' value='$v'>\n";
            }
        } else {
            $val = reclaw($val);
            $inputs .= "$tabs<input type='hidden' name='$key' value='$val'>\n";
        }
    }
    return $inputs;
}


function venueSimilarDiv($venue, $game, $repost){
//echo "<br>VENUESIMILARDIV<br>";
    global $qwikURL;
    $similarVenueInputs = similarVenueButtons($venue, $game, "\t\t\t");
    if(empty($similarVenueInputs)){
        return '';
    }

    $repostInputs = repostIns($repost, "\t\t\t");
    $repost = $repost['repost'];

    $div = 
    "<div>
         <a id='back-icon' class='fa fa-arrow-circle-o-left  back'></a>
        <br>
        $similar
        <form action='$repost' method='post' class='center  transparent'>
$repostInputs
$similarVenueInputs
        </form>
    </div>\n";
    
    return $div;
}


function similarVenueInputs($venue, $game, $tabs=''){
    $similar = similarVenues($venue['name'], $game);

    if (count($similar) == 0){
        return "";
    }

    $inputs = "";
    foreach($similar as $existing){
        $existing = implode(', ',explode('|',$existing));
        $inputs .= "$tabs<input type='submit' name='vid' value='$existing'>\n";
    }
    return $inputs;
}


function similarVenueButtons($venue, $game, $tabs=''){
    $similar = similarVenues($venue['name'], $game);

    if (count($similar) == 0){
        return "";
    }

    $buttons = "";
    foreach($similar as $existing){
        $name = implode(', ',explode('|',$existing));
        $buttons .= "$tabs<button type='submit' class='venue' name='vid' value='$existing'>$name</button>\n";
    }
    return $buttons;
}


function familiarCheckboxes($player){
    $checkboxes = '';
    $reckons = $player->reckon("email");
    foreach($reckons as $reckon){
        $email = $reckon['email'];
        $rid = $reckon['rival'];
        $checkboxes .= "
            <span class='nowrap'>
                <input type='checkbox' name='invite[]' value='$email'>
                $email
            </span>";
    }
    return $checkboxes;
}


function gameOptions($game='squash', $tabs=''){
    global $games;
    if(empty($game)){
        $game='squash';
    }
    $options = '';
    foreach($games as $val => $txt){
        if ($val == $game){
            $selected = 'selected';
        } else {
            $selected = '';
        }
        $options .= "$tabs<option value='$val' $selected>$txt</option>\n";
    }
    return $options;
}


function regions($player){
    $available = $player->available();
    $countries = array();
    $states = array();
    $towns = array();
    foreach($available as $avail){
        $venueID = $avail->venue;
        $reg = explode('|', $venueID);
        $last = count($reg);
        $countries[] = $reg[$last-1];
        $states[] = $reg[$last-2];
        $towns[] = $reg[$last-3];
    }

    $countries = array_unique($countries);
    $states = array_unique($states);
    $towns = array_unique($towns);

    sort($countries);
    sort($states);
    sort($towns);

    return array_merge($countries, $states, $towns);
}


function regionOptions($player, $tabs){
    $regions = regions($player);    
    $options = '';
    foreach($regions as $region){
           $options .= "$tabs<option value='$region'>$region</option>\n";
    }
    return $options;
}


function countryOptions($country, $tabs=''){
    global $countries;
    if(!isset($country)){
        $country = geolocate('countryCode');
    }
    $options = '';
    foreach($countries as $val => $txt){
        $selected = ($val == $country) ? " selected" : '';
        $options .= "$tabs<option value='$val'$selected>$txt</option>\n";
    }
    return $options;
}


function geolocate($key){
    global $geo;
    if(!isset($geo)){
        $geo = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip='.$_SERVER['REMOTE_ADDR']));
    }
    return $geo["geoplugin_$key"];
}



$countries = array();
$countries['AF'] = "Afghanistan";
$countries['AX'] = "Åland Islands";
$countries['AL'] = "Albania";
$countries['DZ'] = "Algeria";
$countries['AS'] = "American Samoa";
$countries['AD'] = "Andorra";
$countries['AO'] = "Angola";
$countries['AI'] = "Anguilla";
$countries['AQ'] = "Antarctica";
$countries['AG'] = "Antigua and Barbuda";
$countries['AR'] = "Argentina";
$countries['AM'] = "Armenia";
$countries['AW'] = "Aruba";
$countries['AU'] = "Australia";
$countries['AT'] = "Austria";
$countries['AZ'] = "Azerbaijan";
$countries['BS'] = "Bahamas";
$countries['BH'] = "Bahrain";
$countries['BD'] = "Bangladesh";
$countries['BB'] = "Barbados";
$countries['BY'] = "Belarus";
$countries['BE'] = "Belgium";
$countries['BZ'] = "Belize";
$countries['BJ'] = "Benin";
$countries['BM'] = "Bermuda";
$countries['BT'] = "Bhutan";
$countries['BO'] = "Bolivia, Plurinational State of";
$countries['BQ'] = "Bonaire, Sint Eustatius and Saba";
$countries['BA'] = "Bosnia and Herzegovina";
$countries['BW'] = "Botswana";
$countries['BV'] = "Bouvet Island";
$countries['BR'] = "Brazil";
$countries['IO'] = "British Indian Ocean Territory";
$countries['BN'] = "Brunei Darussalam";
$countries['BG'] = "Bulgaria";
$countries['BF'] = "Burkina Faso";
$countries['BI'] = "Burundi";
$countries['KH'] = "Cambodia";
$countries['CM'] = "Cameroon";
$countries['CA'] = "Canada";
$countries['CV'] = "Cape Verde";
$countries['KY'] = "Cayman Islands";
$countries['CF'] = "Central African Republic";
$countries['TD'] = "Chad";
$countries['CL'] = "Chile";
$countries['CN'] = "China";
$countries['CX'] = "Christmas Island";
$countries['CC'] = "Cocos (Keeling) Islands";
$countries['CO'] = "Colombia";
$countries['KM'] = "Comoros";
$countries['CG'] = "Congo";
$countries['CD'] = "Congo, the Democratic Republic of the";
$countries['CK'] = "Cook Islands";
$countries['CR'] = "Costa Rica";
$countries['CI'] = "Côte d'Ivoire";
$countries['HR'] = "Croatia";
$countries['CU'] = "Cuba";
$countries['CW'] = "Curaçao";
$countries['CY'] = "Cyprus";
$countries['CZ'] = "Czech Republic";
$countries['DK'] = "Denmark";
$countries['DJ'] = "Djibouti";
$countries['DM'] = "Dominica";
$countries['DO'] = "Dominican Republic";
$countries['EC'] = "Ecuador";
$countries['EG'] = "Egypt";
$countries['SV'] = "El Salvador";
$countries['GQ'] = "Equatorial Guinea";
$countries['ER'] = "Eritrea";
$countries['EE'] = "Estonia";
$countries['ET'] = "Ethiopia";
$countries['FK'] = "Falkland Islands (Malvinas)";
$countries['FO'] = "Faroe Islands";
$countries['FJ'] = "Fiji";
$countries['FI'] = "Finland";
$countries['FR'] = "France";
$countries['GF'] = "French Guiana";
$countries['PF'] = "French Polynesia";
$countries['TF'] = "French Southern Territories";
$countries['GA'] = "Gabon";
$countries['GM'] = "Gambia";
$countries['GE'] = "Georgia";
$countries['DE'] = "Germany";
$countries['GH'] = "Ghana";
$countries['GI'] = "Gibraltar";
$countries['GR'] = "Greece";
$countries['GL'] = "Greenland";
$countries['GD'] = "Grenada";
$countries['GP'] = "Guadeloupe";
$countries['GU'] = "Guam";
$countries['GT'] = "Guatemala";
$countries['GG'] = "Guernsey";
$countries['GN'] = "Guinea";
$countries['GW'] = "Guinea-Bissau";
$countries['GY'] = "Guyana";
$countries['HT'] = "Haiti";
$countries['HM'] = "Heard Island and McDonald Islands";
$countries['VA'] = "Holy See (Vatican City State)";
$countries['HN'] = "Honduras";
$countries['HK'] = "Hong Kong";
$countries['HU'] = "Hungary";
$countries['IS'] = "Iceland";
$countries['IN'] = "India";
$countries['ID'] = "Indonesia";
$countries['IR'] = "Iran, Islamic Republic of";
$countries['IQ'] = "Iraq";
$countries['IE'] = "Ireland";
$countries['IM'] = "Isle of Man";
$countries['IL'] = "Israel";
$countries['IT'] = "Italy";
$countries['JM'] = "Jamaica";
$countries['JP'] = "Japan";
$countries['JE'] = "Jersey";
$countries['JO'] = "Jordan";
$countries['KZ'] = "Kazakhstan";
$countries['KE'] = "Kenya";
$countries['KI'] = "Kiribati";
$countries['KP'] = "Korea, Democratic People's Republic of";
$countries['KR'] = "Korea, Republic of";
$countries['KW'] = "Kuwait";
$countries['KG'] = "Kyrgyzstan";
$countries['LA'] = "Lao People's Democratic Republic";
$countries['LV'] = "Latvia";
$countries['LB'] = "Lebanon";
$countries['LS'] = "Lesotho";
$countries['LR'] = "Liberia";
$countries['LY'] = "Libya";
$countries['LI'] = "Liechtenstein";
$countries['LT'] = "Lithuania";
$countries['LU'] = "Luxembourg";
$countries['MO'] = "Macao";
$countries['MK'] = "Macedonia, the former Yugoslav Republic of";
$countries['MG'] = "Madagascar";
$countries['MW'] = "Malawi";
$countries['MY'] = "Malaysia";
$countries['ML'] = "Mali";
$countries['MT'] = "Malta";
$countries['MH'] = "Marshall Islands";
$countries['MQ'] = "Martinique";
$countries['MR'] = "Mauritania";
$countries['MU'] = "Mauritius";
$countries['YT'] = "Mayotte";
$countries['MX'] = "Mexico";
$countries['FM'] = "Micronesia, Federated States of";
$countries['MD'] = "Moldova, Republic of";
$countries['MC'] = "Monaco";
$countries['MN'] = "Mongolia";
$countries['ME'] = "Montenegro";
$countries['MS'] = "Montserrat";
$countries['MA'] = "Morocco";
$countries['MZ'] = "Mozambique";
$countries['MM'] = "Myanmar";
$countries['NA'] = "Namibia";
$countries['NR'] = "Nauru";
$countries['NP'] = "Nepal";
$countries['NL'] = "Netherlands";
$countries['NC'] = "New Caledonia";
$countries['NZ'] = "New Zealand";
$countries['NI'] = "Nicaragua";
$countries['NE'] = "Niger";
$countries['NG'] = "Nigeria";
$countries['NU'] = "Niue";
$countries['NF'] = "Norfolk Island";
$countries['MP'] = "Northern Mariana Islands";
$countries['NO'] = "Norway";
$countries['OM'] = "Oman";
$countries['PK'] = "Pakistan";
$countries['PW'] = "Palau";
$countries['PS'] = "Palestinian Territory, Occupied";
$countries['PA'] = "Panama";
$countries['PG'] = "Papua New Guinea";
$countries['PY'] = "Paraguay";
$countries['PE'] = "Peru";
$countries['PH'] = "Philippines";
$countries['PN'] = "Pitcairn";
$countries['PL'] = "Poland";
$countries['PT'] = "Portugal";
$countries['PR'] = "Puerto Rico";
$countries['QA'] = "Qatar";
$countries['RE'] = "Réunion";
$countries['RO'] = "Romania";
$countries['RU'] = "Russian Federation";
$countries['RW'] = "Rwanda";
$countries['BL'] = "Saint Barthélemy";
$countries['SH'] = "Saint Helena, Ascension and Tristan da Cunha";
$countries['KN'] = "Saint Kitts and Nevis";
$countries['LC'] = "Saint Lucia";
$countries['MF'] = "Saint Martin (French part)";
$countries['PM'] = "Saint Pierre and Miquelon";
$countries['VC'] = "Saint Vincent and the Grenadines";
$countries['WS'] = "Samoa";
$countries['SM'] = "San Marino";
$countries['ST'] = "Sao Tome and Principe";
$countries['SA'] = "Saudi Arabia";
$countries['SN'] = "Senegal";
$countries['RS'] = "Serbia";
$countries['SC'] = "Seychelles";
$countries['SL'] = "Sierra Leone";
$countries['SG'] = "Singapore";
$countries['SX'] = "Sint Maarten (Dutch part)";
$countries['SK'] = "Slovakia";
$countries['SI'] = "Slovenia";
$countries['SB'] = "Solomon Islands";
$countries['SO'] = "Somalia";
$countries['ZA'] = "South Africa";
$countries['GS'] = "South Georgia and the South Sandwich Islands";
$countries['SS'] = "South Sudan";
$countries['ES'] = "Spain";
$countries['LK'] = "Sri Lanka";
$countries['SD'] = "Sudan";
$countries['SR'] = "Suriname";
$countries['SJ'] = "Svalbard and Jan Mayen";
$countries['SZ'] = "Swaziland";
$countries['SE'] = "Sweden";
$countries['CH'] = "Switzerland";
$countries['SY'] = "Syrian Arab Republic";
$countries['TW'] = "Taiwan, Province of China";
$countries['TJ'] = "Tajikistan";
$countries['TZ'] = "Tanzania, United Republic of";
$countries['TH'] = "Thailand";
$countries['TL'] = "Timor-Leste";
$countries['TG'] = "Togo";
$countries['TK'] = "Tokelau";
$countries['TO'] = "Tonga";
$countries['TT'] = "Trinidad and Tobago";
$countries['TN'] = "Tunisia";
$countries['TR'] = "Turkey";
$countries['TM'] = "Turkmenistan";
$countries['TC'] = "Turks and Caicos Islands";
$countries['TV'] = "Tuvalu";
$countries['UG'] = "Uganda";
$countries['UA'] = "Ukraine";
$countries['AE'] = "United Arab Emirates";
$countries['GB'] = "United Kingdom";
$countries['US'] = "United States";
$countries['UM'] = "United States Minor Outlying Islands";
$countries['UY'] = "Uruguay";
$countries['UZ'] = "Uzbekistan";
$countries['VU'] = "Vanuatu";
$countries['VE'] = "Venezuela, Bolivarian Republic of";
$countries['VN'] = "Viet Nam";
$countries['VG'] = "Virgin Islands, British";
$countries['VI'] = "Virgin Islands, U.S.";
$countries['WF'] = "Wallis and Futuna";
$countries['EH'] = "Western Sahara";
$countries['YE'] = "Yemen";
$countries['ZM'] = "Zambia";
$countries['ZW'] = "Zimbabwe";
                              
                                                                



                                                                                                                        
?>
