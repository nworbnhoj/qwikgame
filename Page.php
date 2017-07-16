<?php

require_once 'qwik.php';
require_once 'logging.php';


class Page {

    private $icons = array (
        'INFO_ICON' => INFO_ICON,
        'TICK_ICON' => TICK_ICON,
        'CROSS_ICON'=> CROSS_ICON,
        'THUMB_UP_ICON' => THUMB_UP_ICON,
        'THUMB_DN_ICON' => THUMB_DN_ICON
    );


    private $log;
    private $req;
    private $player;
    private $language;

	public function __construct(){
	    global $subdomain;
        $this->log = new Logging();
        $this->log->lfile("/tmp/$subdomain.qwikgame.org.log");

        $this->req = $this->validate($_POST);
        if (!$this->req){
            $this->req = $this->validate($_GET);
        }
        if (!$this->req) {
            $this->req = array();
        }

        $this->logReq($this->req);
        $this->player = $this->login($this->req);

        $this->language = $this->language($this->req, $this->player);
    }


    public function serve($template='index'){
        $this->processRequest();
        $variables = $this->variables($this->player);
        $html = $this->html($template, $variables);
        echo($html);
    }


    public function processRequest(){

    }



    public function variables(){
        return array();
    }



    public function html($template, $variables){
        $lang = $this->language;
        $html = file_get_contents("lang/$lang/$template.html");
    //	do{
	    $html = $this->replicate($html, $this->player, $this->req);
        $html = $this->populate($html, $variables);
	    $html = $this->translate($html, $this->language);
    //	} while (preg_match("\<v\>([^\<]+)\<\/v\>", $html) != 1);
    //	} while (strstr($html, "<v>"));
        return $html;
    }




    public function player(){
        return $this->player;
    }


    public function req($key=Null, $value=Null){
        if(is_null($key)){
            return $this->req;
        } elseif (is_null($value) && isset($this->req[$key])){
            return $this->req[$key];
        } else {
            $this->req[$key] = $value;
        }
        return null;
    }


    public function log(){
        return $this->log;
    }


    public function qwik(){
        return $this->req('qwik');
    }


    function logg($msg){
        $this->log->lwrite($msg);
        $this->log->lclose();
    }


    function logReq($req){
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
        $this->log->lwrite($msg);
        $this->log->lclose();
    }


    function logEmail($type, $pid, $game, $vid, $time){
        $p = substr($pid, 0, 4);
        $msg = "email $type pid=$p $game $vid $time";
        $this->log->lwrite($msg);
        $this->log->lclose();
    }


    function logMsg($msg){
        $this->log->lwrite($msg);
        $this->log->lclose();
    }




    /********************************************************************************
    Return the $req data iff ALL variables are valid, or FALSE otherwise

    $req    ArrayMap    url parameters from post&get
    ********************************************************************************/
    private function validate($req){
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
            'smtwtfs'  => array('filter' => FILTER_VALIDATE_INT, 'options' => $hrs_opt),
            'address'  => FILTER_DEFAULT,
            'ability'  => array('filter' => FILTER_VALIDATE_INT, 'options' => $ability_opt),
            'account'  => FILTER_DEFAULT,
            'country'  => array('filter' => FILTER_CALLBACK,    'options' => 'validateCountry'),
            'email'    => FILTER_VALIDATE_EMAIL,
            'Fri'      => array('filter' => FILTER_VALIDATE_INT, 'options' => $hrs_opt),
            'filename' => FILTER_DEFAULT,
            'game'     => array('filter' => FILTER_CALLBACK,    'options' => 'validateGame'),
            'id'       => array('filter' => FILTER_CALLBACK,    'options' => 'validateID'),
            'invite'   => array('filter' => FILTER_CALLBACK,    'options' => 'validataInvite'),
            'Mon'      => array('filter' => FILTER_VALIDATE_INT, 'options' => $hrs_opt),
            'msg'      => FILTER_DEFAULT,
            'name'     => FILTER_DEFAULT,
            'nickname' => FILTER_DEFAULT,
            'parity'   => array('filter' => FILTER_CALLBACK,     'options' => 'validateParity'),
            'phone'    => array('filter' => FILTER_CALLBACK,     'options' => 'validatePhone'),
            'pid'      => array('filter' => FILTER_CALLBACK,     'options' => 'validatePID'),
            'qwik'     => array('filter' => FILTER_CALLBACK,     'options' => 'validateQwik'),
            'Sat'      => array('filter' => FILTER_VALIDATE_INT, 'options' => $hrs_opt),
            'state'    => FILTER_DEFAULT,
            'suburb'   => FILTER_DEFAULT,
            'Sun'      => array('filter' => FILTER_VALIDATE_INT, 'options' => $hrs_opt),
            'Thu'      => array('filter' => FILTER_VALIDATE_INT, 'options' => $hrs_opt),
            'time'     => FILTER_DEFAULT,
            'today'    => array('filter' => FILTER_VALIDATE_INT, 'options' => $hrs_opt),
            'token'    => array('filter' => FILTER_CALLBACK,     'options' => 'validateToken'),
            'tomorrow' => array('filter' => FILTER_VALIDATE_INT, 'options' => $hrs_opt),
            'Tue'      => array('filter' => FILTER_VALIDATE_INT, 'options' => $hrs_opt),
            'tz'       => FILTER_DEFAULT,
            'region'   => FILTER_DEFAULT,
            'rep'      => array('filter' => FILTER_VALIDATE_INT, 'options' => $rep_opt),
            'repost'   => array('filter' => FILTER_CALLBACK,     'options' => 'validateRepost'),
            'rival'    => FILTER_VALIDATE_EMAIL,
            'title'    => FILTER_DEFAULT,
    //        'url'        => FILTER_VALIDATE_URL,
            'url'      => FILTER_DEFAULT,
            'venue'    => FILTER_DEFAULT,
            'Wed'      => array('filter' => FILTER_VALIDATE_INT, 'options' => $hrs_opt)
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


    private function validateGame($val){
        global $games;
        return array_key_exists($val, $games) ? $val : FALSE;
    }


    private function validateCountry($val){
        global $countries;
        return array_key_exists($val, $countries) ? $val : FALSE;
    }


    private function validateID($val){
        return strlen($val) == 6 ? $val : FALSE;
    }


    private function validateInvite($val){
        if (is_array($val)){
            return true;    // *********** more validation required **************
        }
        return false;
    }


    private function validateParity($val){
        global $parityFilter;
        return in_array($val, $parityFilter) ? $val : FALSE;
    }


    private function validatePID($val){
        return strlen($val) == 64 ? $val : FALSE;
    }


    private function validatePhone($val){
        return strlen($val) <= 10 ? $val : FALSE;
    }


    private function validateRepost($val){
        return $val;
    }


    private function validateQwik($val){
        global $qwiks;
        return in_array($val, $qwiks) ? $val : FALSE;
    }


    private function validateToken($val){
        return strlen($val) == 10 ? $val : FALSE;
    }



    /********************************************************************************
    Return the XML data for the current logged in player (if any)

    $req    ArrayMap    url parameters from post&get
    ********************************************************************************/
    private function login($req){
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
        $player = new Player($pid, $this->log, TRUE);

        if(isset($openSession)){
            return $player;
        }

        if($player->isValidToken($token)){                 // LOGIN with token
            $this->logMsg("login: valid token " . snip($pid));
            $_SESSION['pid'] = $pid;
            $_SESSION['lang'] = (string) $player->lang();
            setcookie("pid", "$pid", time() + 3*Player::MONTH, "/");
            setcookie("token", "$token", time() + 3*Player::MONTH, "/");
            return $player;
        } else {
            $this->logMsg("login: invalid token pid=" . snip($pid));
        }

        if(empty($player->email()) && isset($email)){            // LOGIN anon player
            $this->logMsg("login: anon player " . snip($pid));
            emailWelcome($email, $pid, $player->token(Player::MONTH));
            setcookie("pid", '', time()-Player::DAY, "/");
            setcookie("token", '', time()-Player::DAY, "/");
            $_SESSION['pid'] = $pid;
            $_SESSION['lang'] = (string) $player->lang();
            return $player;
        }

        if(isset($email) && $req['qwik'] == 'recover'){            // account recovery
            $this->logMsg("login: recover account " . snip($pid));                 // todo rate limit
            emailLogin($email, $pid, $player->token(Player::DAY));
        }
    }


    /********************************************************************************
    Logout the current player by deleting both the $_SESSION and the longer term
    $_COOKIE
    ********************************************************************************/
    public function logout(){
        if (isset($_SESSION)){
            $pid = $_SESSION['pid'];
            $this->logMsg("logout $pid");
            unset($_SESSION['pid']);
        }
        setcookie("pid", "", time() - Player::DAY);
        setcookie("token", "", time() - Player::DAY);
        global $qwikURL;
        header("location: $qwikURL");
    }




    /********************************************************************************
    Return the current player language or default

    $req    ArrayMap    url parameters from post&get
    $player    XML            player data
    ********************************************************************************/

    private function language($req, $player){
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
    public function translate($html, $lang, $fb='en'){
        $strings = $GLOBALS[$lang];
        $fallback = $GLOBALS[$fb];
        $pattern = '!(?s)\<t\>([^\<]+)\<\/t\>!';
        $tr = function($match) use ($strings, $fallback){
            $key = $match[1];
            $st = $strings[$key];
            if(isset($strings[$key])){
                return $strings[$key];
            } else if (isset($fallback[$key])){
                $this->logMsg("translation missing for $key");
                return $fallback[$key];
            } else {
                $this->logMsg("translation missing for en $key");
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
    public function populate($html, $variables){
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
    public function replicate($html, $player, $req){
        $tr = function($match) use ($player, $req){
            $id = $match[3];
            $html = $match[4];
            switch ($id){
                case 'repost':    return $this->replicatePost($html, $req);                break;
                case 'language':  return $this->replicateLanguages($html);                break;
                case 'games':     return $this->replicateGames($html, $req);                break;
                case 'venues':    return $this->replicateVenues($html);                    break;
                case 'similarVenues': return $this->replicateSimilarVenues($html, $req);    break;
                case 'keen':
                case 'invitation':
                case 'accepted':
                case 'confirmed':
                case 'feedback':
                case 'cancelled':
                case 'history':    return $this->replicateMatches($player, $html, $id);    break;
                case 'available':  return $this->replicateAvailable($player, $html);        break;
                case 'rivalEmail': return $this->replicateEmailCheck($player, $html);        break;
                case 'familiar':   return $this->replicateFamiliar($player, $html);        break;
                case 'ability':    return $this->replicateAbility($player, $html);        break;
                case 'reckon':     return $this->replicateReckons($player, $html);        break;
                case 'uploads':    return $this->replicateUploads($player, $html);        break;
                default:           return '';
            }
        };
        $pattern = "!(?s)\<repeat((\sid='(.+?)')|[^\>]*)\>(.+?)\<\/repeat\>!";
        return  preg_replace_callback($pattern, $tr, $html);
    }


    private function replicatePost($html, $req){
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


    public function replicateGames($html, $req){
        global $games;
        $default = $req['game'];
        $group = '';
        foreach($games as $game => $name){
            $vars = array(
                'game'      => $game,
                'name'      => $name,
                'selected'  => ($game == $default ? 'selected' : '')
            );
            $group .= Page::populate($html, $vars);
        }
        return $group;
    }


    private function replicateVenues($html, $default){
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


    private function replicateSimilarVenues($html, $req){
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


    private function replicateMatches($player, $html, $status){
        if(!$player){ return; }
        $group = '';
        $playerVars = $this->playerVariables($player);
        foreach($player->matchStatus($status) as $matchXML) {
            $match = new Match($player, $matchXML);
            $matchVars = $match->variables();
            $vars = $playerVars + $matchVars + $this->icons;
            $vars['venueLink'] = venueLink($match->vid(), $player, $match->game());
            $group .= populate($html, $vars);
        }
        return $group;
    }


    private function replicateAvailable($player, $html){
        global $games;
        if(!$player){ return; }
        $group = '';
        $playerVars = $this->playerVariables($player);
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
            $vars = $playerVars + $availVars + $this->icons;
            $group .= populate($html, $vars);
        }
        return $group;
    }


    private function replicateEmailCheck($player, $html){
        if(!$player){ return; }
        $group = '';
        $playerVars = $this->playerVariables($player);
        $reckoning = $player->reckon("email");
        foreach($reckoning as $reckon){
            $game = $reckon['game'];
            $reckonVars = array('email' => $reckon['email']);
            $vars = $playerVars + $reckonVars ;
            $group .= populate($html, $vars);
        }
        return $group;
    }


    private function replicateFamiliar($player, $html){
        global $games;
        if(!$player){ return; }
        $group = '';
        $playerVars = $this->playerVariables($player);
        $reckoning = $player->reckon("rival");
        foreach($reckoning as $reckon){
            $game = $reckon['game'];
            $reckonVars = array(
                'id'        => $reckon['id'],
                'email'     => $reckon['email'],
                'game'      => $games["$game"],
                'parity'    => parityStr($reckon['parity'])
            );
            $vars = $playerVars + $reckonVars + $this->icons;
            $group .= populate($html, $vars);
        }
        return $group;
    }


    private function replicateAbility($player, $html){
        global $games;
        if(!$player){ return; }
        $group = '';
        $abilities = array('<t>very_weak</t>', '<t>weak</t>', '<t>competent</t>', '<t>strong</t>', '<t>very_strong</t>');
        $playerVars = $this->playerVariables($player);
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
            $vars = $playerVars + $reckonVars + $this->icons;
            $group .= populate($html, $vars);
        }
        return $group;
    }


    private function replicateLanguages($html){
        global $languages;
        $group = '';
        $current = $_SESSION['lang'];
        foreach($languages as $code => $lang){
            $vars = array(
                'code' => $code,
                'language' => $lang,
                'selected' => $code == $current ? 'selected' : ''
            );
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }


    private function replicateReckons($player, $html){
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


    private function replicateUploads($player, $html){
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


    function playerVariables($player){
        return array(
            'target'    => 'player.php#matches',
            'reputation'=> $this->repStr($player)
        );
    }



    function repStr($player){
        $word = $player->repWord();
        return empty($word) ? 'AAAAAA' : " with a $word reputation";
    }


    function datalists(){
        global $games;
        $datalists = '';
        foreach($games as $game => $name){
            $datalists .= "\n\n" . venueDatalist($game);
        }
        return $datalists;
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

}

?>
