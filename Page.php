<?php

require_once 'qwik.php';
require_once 'logging.php';
require_once 'Defend.php';


class Page {


    static $log;
    static $icons;

    private $template;
    private $req;
    private $player;
    private $language;

	public function __construct($template='index'){	    
	    $this->template = $template;
       
        $defend = new Defend();
        $this->req = $defend->request();

        $this->logReq($this->req);
        $this->player = $this->login($this->req);

        $this->language = $this->language($this->req, $this->player);
    }


    // https://stackoverflow.com/questions/693691/how-to-initialize-static-variables
    static function initStatic(){
        self::$log = new Logging();
        self::$log->lfile("/tmp/".SUBDOMAIN.".qwikgame.org.log");

        self::$icons = array (
           'INFO_ICON' => INFO_ICON,
            'TICK_ICON' => TICK_ICON,
            'CROSS_ICON'=> CROSS_ICON,
            'THUMB_UP_ICON' => THUMB_UP_ICON,
            'THUMB_DN_ICON' => THUMB_DN_ICON
        );
    }


    public function serve(){
        $this->processRequest();
        $html = $this->html();
        echo($html);
    }


    public function processRequest(){
        return null;
    }


    public function variables(){
        global $games;
        $game = $this->req('game');
        $vars = array(
            'CROSS_ICON'    => CROSS_ICON,
            'COMMENT_ICON'  => COMMENT_ICON,
            'EMAIL_ICON'    => EMAIL_ICON,
            'FACEBOOK_ICON' => FACEBOOK_ICON,
            'FEMALE_ICON'   => FEMALE_ICON,
            'HOME_ICON'     => HOME_ICON,
            'INFO_ICON'     => INFO_ICON,
		    'LANG_ICON'		=> LANG_ICON,
            'LOGOUT_ICON'   => '',
            'MALE_ICON'     => MALE_ICON,
            'RELOAD_ICON'   => RELOAD_ICON,
            'THUMB_DN_ICON' => THUMB_DN_ICON,
            'THUMB_UP_ICON' => THUMB_UP_ICON,
            'TWITTER_ICON'  => TWITTER_ICON,
            'homeURL'       => QWIK_URL,
            'termsURL'		=> TERMS_URL,
            'privacyURL'	=> PRIVACY_URL,
            'flyerLink'  	=> FLYER_LNK,
            'thumb-up'		=> "<span class='".THUMB_UP_ICON."'></span>",
            'thumb-dn'		=> "<span class='".THUMB_DN_ICON."'></span>",            
            'game'          => isset($game) ? $games["$game"] : '<v>game</v>'
        );
        
        if ($this->player != null){
            $vars['pid']         = $this->player->id();
            $vars['LOGOUT_ICON'] = LOGOUT_ICON;
        }
        
        return $vars;
    }



    public function html(){
        $lang = $this->language;
        $template = $this->template;
        $html = file_get_contents("lang/$lang/$template.html");
    //	do{
	    $html = $this->replicate($html, $this->player, $this->req);
        $html = $this->populate($html, $this->variables());
	    $html = $this->translate($html, $this->language);
    //	} while (preg_match("\<v\>([^\<]+)\<\/v\>", $html) != 1);
    //	} while (strstr($html, "<v>"));
        return $html;
    }




    public function player(){
        return $this->player;
    }


    public function req($key=null, $value=null){
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
        return self::$log;
    }


    public function qwik(){
        return $this->req('qwik');
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
        self::$log->lwrite($msg);
        self::$log->lclose();
    }


    function logEmail($type, $pid, $game='', $vid='', $time=''){
        $p = substr($pid, 0, 4);
        $msg = "email $type pid=$p $game $vid $time";
        self::$log->lwrite($msg);
        self::$log->lclose();
    }


    function logMsg($msg){
        self::$log->lwrite($msg);
        self::$log->lclose();
    }







    /********************************************************************************
    Return the XML data for the current logged in player (if any)

    $req    ArrayMap    url parameters from post&get
    ********************************************************************************/
    private function login($req){
        $openSession = false;
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

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
            $pid = Player::anonID($email);          // and derive the pid from the email
            $token = isset($req['token']) ? $req['token'] : null;
        } else {                            // anonymous session: no player identifier
            return;                         // RETURN login fail
        }
                                            // OK playerID
        $player = new Player($pid, self::$log, TRUE);

        if($openSession){
            return $player;
        }

        if($player->isValidToken($token)){                 // LOGIN with token
            $this->logMsg("login: valid token " . snip($pid));
            $_SESSION['pid'] = $pid;
            $_SESSION['lang'] = $player->lang();
            setcookie("pid", $pid, time() + 3*Player::MONTH, "/");
            setcookie("token", $token, time() + 3*Player::MONTH, "/");
            return $player;
        } else {
            $this->logMsg("login: invalid token pid=" . snip($pid));
        }

        if(empty($player->email()) && isset($email)){            // LOGIN anon player
            $this->logMsg("login: anon player " . snip($pid));
            $token = $player->token(Player::MONTH);
            $player->save();
            setcookie("pid", $pid, time() + Player::DAY, "/");
            setcookie("token", $token, time() + Player::DAY, "/");
            $_SESSION['pid'] = $pid;
            $_SESSION['lang'] = $player->lang();
            $this->emailWelcome($email, $pid, $token);
            return $player;
        }

        if(isset($email) && $req['qwik'] == 'recover'){            // account recovery
            $this->logMsg("login: recover account " . snip($pid));                 // todo rate limit
            $this->emailLogin($email, $pid, $player->token(Player::DAY));
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
        header("location: ".QWIK_URL);
    }




    /********************************************************************************
    Return the current player language or default

    $req    ArrayMap    url parameters from post&get
    $player    XML            player data
    ********************************************************************************/

    private function language($req, $player){
        global $languages;
//        header('Cache-control: private'); // IE 6 FIX

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
//                $this->logMsg("translation missing for $key");
                return $fallback[$key];
            } else {
//                $this->logMsg("translation missing for en $key");
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
        $tr = function($match) use ($variables){
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
                case 'repost':    return $this->replicatePost($html, $req);              break;
                case 'language':  return $this->replicateLanguages($html);               break;
                case 'games':     return $this->replicateGames($html, $req);             break;
                case 'venues':    return $this->replicateVenues($html);                  break;
                case 'similarVenues': return $this->replicateSimilarVenues($html, $req); break;
                case 'keen':
                case 'invitation':
                case 'accepted':
                case 'confirmed':
                case 'feedback':
                case 'cancelled':
                case 'history':    return $this->replicateMatches($player, $html, $id);  break;
                case 'available':  return $this->replicateAvailable($player, $html);     break;
                case 'rivalEmail': return $this->replicateEmailCheck($player, $html);    break;
                case 'familiar':   return $this->replicateFamiliar($player, $html);      break;
                case 'ability':    return $this->replicateAbility($player, $html);       break;
                case 'reckon':     return $this->replicateReckons($player, $html);       break;
                case 'uploads':    return $this->replicateUploads($player, $html);       break;
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
                    $group .= $this->populate($html, $vars);
                }
            } else {
                $vars = array(
                    'name'      => $name,
                    'value'     => $value,
                );
                $group .= $this->populate($html, $vars);
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
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }


    private function replicateVenues($html, $default){
        return "replicateVenues() has not been implemented";
    echo "<br>REPLICATEVENUES<br>$html";
        $group = '';
        $venueIDs = $this->listVenues('squash'); //$game);
        foreach($venueIDs as $vid => $playerCount){
    echo "<br>$vid";
            $vars = array(
                'playerCount'   => $playerCount,
                'vid'              => $vid,
                'venueName'      => explode('|', $vid)[0]
            );
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }


    private function replicateSimilarVenues($html, $req){
        $group = '';
        $vid = $req['vid'];
        $game = $req['game'];
    //    $similar = $this->similarVenues($req['venue'], $req['game']);
        $similar = array_slice($this->similarVenues($req['venue']), 0, 10);
        foreach($similar as $vid){
            $venue = readVenueXML($vid);
            $players = isset($venue) ? $venue->xpath("player") : array() ;
            $vars = array(
                'vid'        => $vid,
                'name'        => implode(', ',explode('|',$vid)),
                'players'    => count($players),
            );
            $group .= $this->populate($html, $vars);
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
            $vars = $playerVars + $matchVars + self::$icons;
            $vars['venueLink'] = $this->venueLink($match->vid());
            $group .= $this->populate($html, $vars);
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
            $game = (string) $avail['game'];
            $availVars = array(
                'id'        => (string) $avail['id'],
                'game'      => $games[$game],
                'parity'    => (string) $avail['parity'],
                'weekSpan'  => $this->weekSpan($avail),
                'venueLink' => $this->venueLink($avail->venue)
            );
            $vars = $playerVars + $availVars + self::$icons;
            $group .= $this->populate($html, $vars);
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
            $group .= $this->populate($html, $vars);
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
            $vars = $playerVars + $reckonVars + self::$icons;
            $group .= $this->populate($html, $vars);
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
            $vars = $playerVars + $reckonVars + self::$icons;
            $group .= $this->populate($html, $vars);
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
        $regions = $this->regions($player);
        $group = '';
        foreach($regions as $region){
            $vars = array(
                'region' => $region,
            );
            $group .= $this->populate($html, $vars);
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
            $group .= $this->populate($html, $vars);
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


    public function datalists(){
        global $games;
        $datalists = '';
        foreach($games as $game => $name){
            $datalists .= "\n\n" . $this->venueDatalist($game);
        }
        return $datalists;
    }
    
    
    private function venueDatalist($game){
        $vids =venues($game);
        $datalist = "<datalist id='venue-$game'>\n";
        foreach($vids as $vid){
            $svid = Venue::svid($vid);
            $datalist .= "\t<option value='$svid'>\n";
        }
        $datalist .= "</datalist>\n";
        return $datalist;
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
    
    
    public function regions($player){
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


    function countryOptions($country, $tabs=''){
        global $countries;
        if(!isset($country)){
            $country = $this->geolocate('countryCode');
        }
        $options = '';
        foreach($countries as $val => $txt){
            $selected = ($val == $country) ? " selected" : '';
            $options .= "$tabs<option value='$val'$selected>$txt</option>\n";
        }
        return $options;
    }
    
    
    private function weekSpan($xml){
        $html = "";
        $hrs = $xml->xpath("hrs");
        foreach($hrs as $hr){
            $html .= daySpan($hr, $hr['day']);
        }
        return $html;
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
    


    private function venueLink($vid){
        $name = explode("|", $vid)[0];
        $boldName = $this->firstWordBold($name);
        $url = QWIK_URL."/venue.php?vid=$vid";
        $link = "<a href='$url'>$boldName</a>";
        return $link;
    }


    private function firstWordBold($phrase){
        $words = explode(' ', $phrase);
        $first = $words[0];
        $words[0] = "<b>$first</b>";
        return implode(' ', $words);
    }
    
    
    



    public function geolocate($key){
        global $geo;
        if(!isset($geo)){
            $geo = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip='.$_SERVER['REMOTE_ADDR']));
        }
        return $geo["geoplugin_$key"];
    }



    private function emailWelcome($email, $id, $token){
        $authURL = $this->authURL($email, $token);

        $subject = "Welcome to qwikgame.org";

        $msg  = "<p>\n";
        $msg .= "\tPlease click this link to <b>activate</b> your qwikgame account:<br>\n";
        $msg .= "\t<a href='$authURL' target='_blank'>$authURL</a>\n";
        $msg .= "\t\t\t</p>\n";
        $msg .= "\t\t\t<p>\n";
        $msg .= "\t\t\tBy clicking on these links you are agreeing to be bound by these \n";
        $msg .= "\t\t\t<a href='".TERMS_URL."' target='_blank'>\n";
        $msg .= "\t\t\tTerms & Conditions</a>";
        $msg .= "</p>\n";
        $msg .= "<p>\n";
        $msg .= "\tIf you did not expect to receive this request, then you can safely ignore and delete this email.\n";
        $msg .= "<p>\n";

        Player::qwikEmail($email, $subject, $msg, $id, $token);
        self::logEmail('welcome', $id);
    }


    private function emailLogin($email, $id, $token){
        $authURL = $this->authURL($email, $token);

        $subject = 'qwikgame.org login link';

        $msg  = "<p>\n";
        $msg .= "\tPlease click this link to login and Bookmark for easy access:<br>\n";
        $msg .= "\t<a href='$authURL' target='_blank'>$authURL</a>\n";
        $msg .= "\t\t\t</p>\n";
        $msg .= "<p>\n";
        $msg .= "\tIf you did not expect to receive this request, then you can safely ignore and delete this email.\n";
        $msg .= "<p>\n";

        Player::qwikEmail($email, $subject, $msg, $id, $token);
        self::logEmail('login', $id);
    }



    function authURL($email, $token) {    
        return QWIK_URL."/player.php?qwik=login&email=$email&token=$token";
    }




}

Page::initStatic();

?>
