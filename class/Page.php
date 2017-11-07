<?php

require_once 'class/Qwik.php';
require_once 'Defend.php';
require_once 'Translation.php';
require_once 'Player.php';
require_once 'Venue.php';
require_once 'Hours.php';

class Page extends Qwik {

    const BACK_ICON      = 'fa fa-chevron-circle-left icon';
    const COMMENT_ICON   = 'fa fa-comment-o comment';
    const CROSS_ICON     = 'fa fa-times-circle cross';
    const EMAIL_ICON     = 'fa fa-envelope-o icon';
    const FACEBOOK_ICON  = 'fa fa-facebook icon';
    const FEMALE_ICON    = 'fa fa-female person';
    const HOME_ICON      = 'fa fa-home icon';
    const INFO_ICON      = 'fa fa-question-circle icon';
    const RELOAD_ICON    = 'fa fa-refresh icon';
    const LANG_ICON      = 'fa fa-globe icon';
    const LOGOUT_ICON    = 'fa fa-power-off icon';
    const MALE_ICON      = 'fa fa-male person';
    const MAP_ICON       = 'fa fa-map-marker';
    const SEND_ICON      = 'fa fa-send';
    const THUMB_DN_ICON  = 'fa fa-thumbs-o-down thumb red';
    const THUMB_UP_ICON  = 'fa fa-thumbs-o-up thumb green';
    const TICK_ICON      = 'fa fa-check-circle tick';
    const TWITTER_ICON   = 'fa fa-twitter icon';
    
    const FLYER_URL    = self::QWIK_URL.'/pdf/qwikgame.org%20flyer.pdf';
    const PRIVACY_URL  = self::QWIK_URL.'/pdf/qwikgame.org%20privacy%20policy.pdf';
    const FACEBOOK_URL = 'https://www.facebook.com/sharer/sharer.php?u='.self::QWIK_URL;
    const TWITTER_URL  = 'https://twitter.com/intent/tweet?text={tagline}&url='.self::QWIK_URL;

    const EMAIL_IMG    = "<img src='img/email.png' alt='email' class='socialmedia'>";
    const FACEBOOK_IMG = "<img src='img/facebook.png' alt='facebook' class='socialmedia'>";
    const TWITTER_IMG  = "<img src='img/twitter.png' alt='twitter' class='socialmedia'>";

    const EMAIL_LNK    = "<a href='mailto:?subject=".self::QWIK_URL."&body=".self::QWIK_URL."%20makes%20it%20easy%20to%20{tagline}&target=_blank'>".self::EMAIL_IMG."</a>";
    const FACEBOOK_LNK = "<a href='".self::FACEBOOK_URL."' target='_blank'>".self::FACEBOOK_IMG."</a>";
    const FLYER_LNK    = "<a href='".self::FLYER_URL."' target='_blank'>{flyer}</a>";
    const TWITTER_LNK  = "<a href='".self::TWITTER_URL."' target='_blank'>".self::TWITTER_IMG."</a>";

    static $translation;

    static $languages = array(
        'zh'=>'中文',
        'es'=>'Español',
        'en'=>'English',
        // 'fr'=>'français',
        // 'hi'=>'हिन्दी भाषा',
        // 'ar'=>'اللغة العربية',
        // 'jp'=>'日本語'
    );

    static $icons;

    private $template;
    private $req;
    private $player;
    private $language;

	public function __construct($template='index'){
        parent::__construct();  
	    $this->template = $template;
	    
	    if (is_null(self::$translation)){
            self::$translation = new Translation('translation.xml', 'lang');
        }
       
        $defend = new Defend();
        $this->req = $defend->request();

        $this->logReq($this->req);
        $this->player = $this->login($this->req);

        $this->language = $this->language($this->req, $this->player);
    }


    // https://stackoverflow.com/questions/693691/how-to-initialize-static-variables
    static function initStatic(){
        self::$icons = array (
            'INFO_ICON'     => self::INFO_ICON,
            'TICK_ICON'     => self::TICK_ICON,
            'CROSS_ICON'    => self::CROSS_ICON,
            'THUMB_UP_ICON' => self::THUMB_UP_ICON,
            'THUMB_DN_ICON' => self::THUMB_DN_ICON
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
        $game = $this->req('game');
        $vars = array(
            'CROSS_ICON'    => self::CROSS_ICON,
            'COMMENT_ICON'  => self::COMMENT_ICON,
            'EMAIL_ICON'    => self::EMAIL_ICON,
            'FACEBOOK_ICON' => self::FACEBOOK_ICON,
            'FEMALE_ICON'   => self::FEMALE_ICON,
            'HOME_ICON'     => self::HOME_ICON,
            'INFO_ICON'     => self::INFO_ICON,
            'LANG_ICON'	    => self::LANG_ICON,
            'LOGOUT_ICON'   => '',
            'MALE_ICON'     => self::MALE_ICON,
            'RELOAD_ICON'   => self::RELOAD_ICON,
            'THUMB_DN_ICON' => self::THUMB_DN_ICON,
            'THUMB_UP_ICON' => self::THUMB_UP_ICON,
            'TWITTER_ICON'  => self::TWITTER_ICON,
            'homeURL'       => self::QWIK_URL,
            'termsURL'      => self::TERMS_URL,
            'privacyURL'    => self::PRIVACY_URL,
            'flyerLink'     => self::FLYER_LNK,
            'thumb-up'      => "<span class='" . self::THUMB_UP_ICON . "'></span>",
            'thumb-dn'      => "<span class='" . self::THUMB_DN_ICON . "'></span>",
            'game'          => isset($game) ? self::games()["$game"] : '[game]'
        );
        
        if ($this->player != null){
            $vars['pid']         = $this->player->id();
            $vars['LOGOUT_ICON'] = self::LOGOUT_ICON;
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
    //	} while (preg_match("\[([^\]]+)\]", $html) != 1);
    //	} while (strstr($html, "["));
        return $html;
    }




    public function player(){
        return $this->player;
    }
    
    public function languages(){
        return self::$translation->languages();
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
                    $msg .= is_array($val) ? print_r($val, true) : $val;
            }
        }
        self::log()->lwrite($msg);
        self::log()->lclose();
    }


    /********************************************************************************
    Return the XML data for the current logged in player (if any)

    $req    ArrayMap    url parameters from post&get
    ********************************************************************************/
    private function login($req){
        $openSession = false;
        if (session_status() == PHP_SESSION_NONE
        && !headers_sent()) {
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
        $player = new Player($pid, TRUE);

        if($openSession){
            return $player;
        }

        if($player->isValidToken($token)){                 // LOGIN with token
            self::logMsg("login: valid token " . self::snip($pid));
            $_SESSION['pid'] = $pid;
            $_SESSION['lang'] = $player->lang();
            if (!headers_sent()){
                setcookie("pid", $pid, time() + 3*Player::MONTH, "/");
                setcookie("token", $token, time() + 3*Player::MONTH, "/");
            }
            return $player;
        } else {
            self::logMsg("login: invalid token pid=" . self::snip($pid));
        }

        if(empty($player->email()) && isset($email)){            // LOGIN anon player
            self::logMsg("login: anon player " . self::snip($pid));
            $token = $player->token(Player::MONTH);
            $player->save();
            if (!headers_sent()){
                setcookie("pid", $pid, time() + Player::DAY, "/");
                setcookie("token", $token, time() + Player::DAY, "/");
            }
            $_SESSION['pid'] = $pid;
            $_SESSION['lang'] = $player->lang();
            $player->emailWelcome($email);
            return $player;
        }

        if(isset($email) && $req['qwik'] == 'recover'){            // account recovery
            self::logMsg("login: recover account " . self::snip($pid));                 // todo rate limit
            $token = $player->token(Player::DAY);
            $player->save();
            $player->emailLogin();
        }
    }


    /********************************************************************************
    Logout the current player by deleting both the $_SESSION and the longer term
    $_COOKIE
    ********************************************************************************/
    public function logout(){
        if (isset($_SESSION) && isset($_SESSION['pid'])){
            $pid = $_SESSION['pid'];
            self::logMsg("logout $pid");
            unset($_SESSION['pid']);
        }
        if (!headers_sent()){
            setcookie("pid", "", time() - Player::DAY);
            setcookie("token", "", time() - Player::DAY);
        }
        $this->goHome();
    }


    public function goHome(){
        if (headers_sent()){
            echo("Redirect failed.<br>");
            echo("Please click on this link: <a href='".self::QWIK_URL."'>this link</a>");
        } else {
            header("location: ".self::QWIK_URL);
        }
    }


    /********************************************************************************
    Return the current player language or default

    $req    ArrayMap    url parameters from post&get
    $player    XML            player data
    ********************************************************************************/

    private function language($req, $player){
        $languages = $this->languages();
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
    Return the html template after replacing {variables} with the requested
    language (or with the fallback language as required)

    $html    String    html template with variables of the form {name}
    $lang    String    language to replace {variables} with
    $fb        String    fallback language for when a translation is missing
    ********************************************************************************/
    public function translate($html, $lang, $fb='en'){
        $translation = self::$translation;
        $pattern = '!(?s)\{([^\}]+)\}!';
        $tr = function($match) use ($translation, $lang, $fb){
            $key = $match[1];
            $phrase = $translation->phrase($key, $lang, $fb);
            return empty($phrase) ? '{'."$key".'}' : $phrase;
        };
        return  preg_replace_callback($pattern, $tr, $html);
    }


    /********************************************************************************
    Return the html template after replacing [variables] with the values provided.

    $html        String        html template with variables of the form [key]
    $variables    ArrayMap    variable name => $value
    ********************************************************************************/
    public function populate($html, $variables){
        $pattern = '!(?s)\[([^\]]+)\]!';
        $tr = function($match) use ($variables){
            $m = $match[1];
            return isset($variables[$m]) ? $variables[$m] : "[$m]";
        };
        return  preg_replace_callback($pattern, $tr, $html);
    }


    /********************************************************************************
    Return the html template after replicating <r>elements</r> with data from $player & $req.

    $html    String        html template with variables of the form [key]
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
        $default = $req['game'];
        $group = '';
        foreach(self::games() as $game => $name){
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
        $group = '';
        $venueIDs = $this->listVenues('squash'); //$game);
        foreach($venueIDs as $vid => $playerCount){
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
        $similar = array_slice($this->similarVenues($req['venue']), 0, 10);
        foreach($similar as $vid){
            $venue = new Venue($vid);
            $vars = array(
                'vid'    => $vid,
                'name'   => implode(', ',explode('|',$vid)),
                'players'=> $venue->playerCount(),
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
        if(!$player){ return; }
        $group = '';
        $playerVars = $this->playerVariables($player);
        $available = $player->available();
        foreach($available as $avail){
            $game = (string) $avail['game'];
            $availVars = array(
                'id'        => (string) $avail['id'],
                'game'      => self::games()[$game],
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
        if(!$player){ return; }
        $group = '';
        $playerVars = $this->playerVariables($player);
        $reckoning = $player->reckon("rival");
        foreach($reckoning as $reckon){
            $game = $reckon['game'];
            $reckonVars = array(
                'id'        => $reckon['id'],
                'email'     => $reckon['email'],
                'game'      => self::games()["$game"],
                'parity'    => self::parityStr($reckon['parity'])
            );
            $vars = $playerVars + $reckonVars + self::$icons;
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }


    private function replicateAbility($player, $html){
        if(!$player){ return; }
        $group = '';
        $abilities = array('{very_weak}', '{weak}', '{competent}', '{strong}', '{very_strong}');
        $playerVars = $this->playerVariables($player);
        $reckoning = $player->reckon("region");
        foreach($reckoning as $reckon){
            $game = $reckon['game'];
            $ability = $reckon['ability'];
            $reckonVars = array(
                'id'        => $reckon['id'],
                'region'    => explode(',', $reckon['region'])[0],
                'game'      => self::games()["$game"],
                'ability'   => $abilities["$ability"]
            );
            $vars = $playerVars + $reckonVars + self::$icons;
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }


    private function replicateLanguages($html){
        $languages = $this->languages();
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
                'tickIcon' => $status == 'uploaded' ? self::TICK_ICON : '',
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
        $datalists = '';
        foreach(self::games() as $game => $name){
            $datalists .= "\n\n" . $this->venueDatalist($game);
        }
        return $datalists;
    }
    
    
    private function venueDatalist($game){
        $vids = self::venues($game);
        $datalist = "<datalist id='venue-$game'>\n";
        foreach($vids as $vid){
            $svid = Venue::svid($vid);
            $datalist .= "\t<option value='$svid'>\n";
        }
        $datalist .= "</datalist>\n";
        return $datalist;
    }


    function gameOptions($game='squash', $tabs=''){
        if(empty($game)){
            $game='squash';
        }
        $options = '';
        foreach(self::games() as $val => $txt){
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
            if ($last >= 3){
                $countries[] = $reg[$last-1];
                $states[] = $reg[$last-2];
                $towns[] = $reg[$last-3];
            } else {
                self::logMsg("warning: unable to extract region '$venueID'");
            }
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
        if(!isset($country)){
            $country = $this->geolocate('countryCode');
        }
        $options = '';
        foreach(self::countries() as $val => $txt){
            $selected = ($val == $country) ? " selected" : '';
            $options .= "$tabs<option value='$val'$selected>$txt</option>\n";
        }
        return $options;
    }


    static public function parityStr($parity){
//echo "<br>PARITYSTR $parity<br>";
        if(!is_numeric("$parity")){
            return '';
        }

        $pf = floatval($parity);
        if($pf <= -2){
            return "{much_weaker}";
        } elseif($pf <= -1){
            return "{weaker}";
        } elseif($pf < 1){
            return "{well_matched}";
        } elseif($pf < 2){
            return "{stronger}";
        } else {
            return "{much_stronger}";
        }
    }


    static public function daySpan($hours, $day='', $clock24hr=FALSE){
        if (count($hours) == 0){
            return "";
        }

        $dayX = substr($day, 0, 3);
        if (count($hours) == 24){
            return "<span class='lolite'><b>$dayX</b></span>";
        }

        $dayP = $clock24hr ? 0 : 12;
        $str =  $clock24hr ? $dayX : '';
        $last = null;
        foreach($hours as $hr){
            $pm = $hr > 12;
            $consecutive = $hr == ($last + 1);
            $str .= $consecutive ? "&middot" : self::clock($last) . ' ';

            if ($pm && !$clock24hr) {
                $str .= "<b>$dayX</b>";
                $dayX = null;
            }

            $str .= $consecutive ? '' : " " . self::clock($hr);
            $last = $hr;
        }
        $str .= $consecutive ? self::clock($last) : '';
        $str .= $dayX!=null ? " <b>$dayX</b>" : '';
        return "<span class='lolite'>$str</span>";
    }


    private function weekSpan($xml){
        $html = "";
        $hrs = $xml->xpath("hrs");
        foreach($hrs as $hr){
            $hours = new Hours($hr);
            $html .= self::daySpan($hours->list(), $hr['day']);
        }
        return $html;
    }
    
    
    
    function similarVenues($description, $game=NULL){
        $similar = array();
        $existingVenues = self::venues($game);
        $words = explode(" ", $description, 5);
        array_walk($words, array($this, 'trim_value'));
    
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


    private function trim_value(&$value)
    {
        $value = trim($value);
    }



    function listVenues($game){
    //echo "<br>LISTVENUES<br>";
        $venues = self::venues($game);
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
        $url = self::QWIK_URL."/venue.php?vid=$vid";
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

}

Page::initStatic();

?>
