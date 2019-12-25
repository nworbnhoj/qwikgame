<?php

require_once 'Html.php';
require_once 'Defend.php';
require_once 'Player.php';
require_once 'Venue.php';
require_once 'Hours.php';
require_once 'Locate.php';

/*******************************************************************************
    Class Page constructs an html page beginning with a html template; 
    replicating html elements (such as rows in a <table>); replacing
    [variables]; and making {translations}.
*******************************************************************************/

class Page extends Html {


    static public function readTemplate($name){
        if(empty($name)){
            return '';
        }

        $template = '';
        try{
            $PATH = Qwik::PATH_LANG.'/'.$this->language();
            $template = file_get_contents("$PATH/$name.html");
        } catch (Throwable $t){
            Qwik::logThrown($t);
            $html = errorHTML();
        } finally {
            return template;
        }
    }


    const ACCOUNT_ICON   = 'fa fa-cog icon';
    const BACK_ICON      = 'fa fa-chevron-circle-left icon';
    const COMMENT_ICON   = 'fa fa-comment-o comment';
    const CROSS_ICON     = 'fa fa-times-circle cross';
    const EMAIL_ICON     = 'fa fa-envelope-o icon';
    const FACEBOOK_ICON  = 'fa fa-facebook icon';
    const FAVORITE_ICON  = 'fa fa-heart icon';
    const FEMALE_ICON    = 'fa fa-female person';
    const FRIEND_ICON    = 'fa fa-user icon';
    const HELP_ICON      = 'fa fa-question-circle icon';
    const HOME_ICON      = 'fa fa-home icon';
    const INFO_ICON      = 'fa fa-info-circle icon';
    const RELOAD_ICON    = 'fa fa-refresh icon';
    const LANG_ICON      = 'fa fa-globe icon';
    const LOGOUT_ICON    = 'fa fa-power-off icon';
    const MALE_ICON      = 'fa fa-male person';
    const MAP_ICON       = 'fa fa-map-marker';
    const MATCH_ICON     = 'fa fa-percent icon';
    const SEND_ICON      = 'fa fa-send';
    const THUMB_DN_ICON  = 'fa fa-thumbs-o-down thumb red';
    const THUMB_UP_ICON  = 'fa fa-thumbs-o-up thumb green';
    const TICK_ICON      = 'fa fa-check-circle tick';
    const TWITTER_ICON   = 'fa fa-twitter icon';
    
    const FLYER_URL    = self::QWIK_URL.'/pdf/qwikgame.org%20flyer.pdf';
    const FACEBOOK_URL = 'https://www.facebook.com/sharer/sharer.php?u='.self::QWIK_URL;
    const TWITTER_URL  = 'https://twitter.com/intent/tweet?text={tagline}&url='.self::QWIK_URL;

    const EMAIL_IMG    = "<img src='img/email.png' alt='email' class='socialmedia'>";
    const FACEBOOK_IMG = "<img src='img/facebook.png' alt='facebook' class='socialmedia'>";
    const TWITTER_IMG  = "<img src='img/twitter.png' alt='twitter' class='socialmedia'>";

    const EMAIL_LNK    = "<a href='mailto:?subject=".self::QWIK_URL."&body=".self::QWIK_URL."%20makes%20it%20easy%20to%20{tagline}&target=_blank'>".self::EMAIL_IMG."</a>";
    const FACEBOOK_LNK = "<a href='".self::FACEBOOK_URL."' target='_blank'>".self::FACEBOOK_IMG."</a>";
    const FLYER_LNK    = "<a href='".self::FLYER_URL."' target='_blank'>{flyer}</a>";
    const TWITTER_LNK  = "<a href='".self::TWITTER_URL."' target='_blank'>".self::TWITTER_IMG."</a>";

    static $icons;
    static $pending;

    private $player;
    private $language;
    private $req;
    private $alert = "";


    /*******************************************************************************
    Class Page is constructed with the name of the file containing a html template.

    $templateName  String  fileName containing the html template.
    *******************************************************************************/
    public function __construct($template){
        parent::__construct($template);

        $defend = new Defend();
        $this->req = $defend->request();

        $this->logReq($this->req);
        $this->player = $this->login($this->req);

        $pageLanguage = $this->selectLanguage($this->req, $this->player);
        parent::language($pageLanguage);
    }


    // https://stackoverflow.com/questions/693691/how-to-initialize-static-variables
    static function initStatic(){
        self::$icons = array (
            'HELP_ICON'     => self::HELP_ICON,
            'INFO_ICON'     => self::INFO_ICON,
            'TICK_ICON'     => self::TICK_ICON,
            'CROSS_ICON'    => self::CROSS_ICON,
            'THUMB_UP_ICON' => self::THUMB_UP_ICON,
            'THUMB_DN_ICON' => self::THUMB_DN_ICON
        );
    }


    /**
     * This is a caching function that ensures the file pending.xml is only read once.
     * Be sure to use &reference when wishing to make changes and call .save() 
     */
    function &pending(){
        if (is_null(self::$pending)){
            self::$pending = new Translation('pending.xml');
        }
        return self::$pending;
    }


    public function serve(){
        try {
            $this->processRequest();
        } catch (Throwable $t){
            Qwik::logThrown($t);
        } finally {
            parent::serve();            
        }
    }


    public function processRequest(){
        return NULL;
    }


    public function variables(){
        $vars = parent::variables();
        $vars['ACCOUNT_ICON']  = self::ACCOUNT_ICON;
        $vars['CROSS_ICON']    = self::CROSS_ICON;
        $vars['COMMENT_ICON']  = self::COMMENT_ICON;
        $vars['EMAIL_ICON']    = self::EMAIL_ICON;
        $vars['FACEBOOK_ICON'] = self::FACEBOOK_ICON;
        $vars['FAVORITE_ICON'] = self::FAVORITE_ICON;
        $vars['FRIEND_ICON']   = self::FRIEND_ICON;
        $vars['FEMALE_ICON']   = self::FEMALE_ICON;
        $vars['HELP_ICON']     = self::HELP_ICON;
        $vars['HOME_ICON']     = self::HOME_ICON;
        $vars['INFO_ICON']     = self::INFO_ICON;
        $vars['LANG_ICON']     = self::LANG_ICON;
        $vars['MALE_ICON']     = self::MALE_ICON;
        $vars['MATCH_ICON']    = self::MATCH_ICON;
        $vars['RELOAD_ICON']   = self::RELOAD_ICON;
        $vars['THUMB_DN_ICON'] = self::THUMB_DN_ICON;
        $vars['THUMB_UP_ICON'] = self::THUMB_UP_ICON;
        $vars['TWITTER_ICON']  = self::TWITTER_ICON;
        $vars['flyerLink']     = self::FLYER_LNK;
        $vars['thumb-up'] = "<span class='" . self::THUMB_UP_ICON . "'></span>";
        $vars['thumb-dn'] = "<span class='" . self::THUMB_DN_ICON . "'></span>";
        $game = (string) $this->req('game');
        $vars['game']  = empty($game) ? '[game]' : self::qwikGames()[$game];
        
        if ($this->player != NULL){
            $vars['pid']         = $this->player->id();
        }

        $vars['message'] = $this->alert;
        
        return $vars;
    }


    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();
        $html = $this->legacyReplicate($html, $this->player, $this->req());
        $html = parent::make($html, $variables);
        return $html;
    }


    public function player(){
        return $this->player;
    }


    public function req($key=NULL, $value=NULL){
        if(is_null($key)){
            return $this->req;
        } elseif (is_null($value) && isset($this->req[$key])){
            return $this->req[$key];
        } else {
            $this->req[$key] = $value;
        }
        return NULL;
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


    function alert($msg){
        $this->alert = empty($this->alert) ? $msg : $this->alert."<br>$msg";
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

        // Locate identification (pid) and authentication (token) if they exist
        if (isset($req['pid'])){            // check in the request
            $pid = $req['pid'];
            $token = $req['token'];
        } elseif (isset($_SESSION['pid'])){ // check in the $_SESSION variable
            $pid = $_SESSION['pid'];
            $openSession = true;
        } elseif (isset($_COOKIE['pid'])){  // check in a $_COOKIE
            $pid = $_COOKIE['pid'];
            $token = $_COOKIE['token'];
        } elseif (isset($req['email'])){    // check for an email in the request
            $email = $req['email'];
            $pid = Player::anonID($email);  // and derive the pid from the email
            $token = isset($req['token']) ? $req['token'] : NULL;
        } else {                            // anonymous session: no player identifier
            return;                         // RETURN login fail
        }

        // Load up the Player from file
        try {
            $player = new Player($pid);
        } catch (RuntimeException $e){
            self::logThown($e);
            self::logMsg("failed to retrieve Player $pid");
            $player = NULL; 
        }

        // return the Player iff authentication is possible
        if($openSession){                   // AUTH: existing session
        } elseif(isset($player)
        && $player->isValidToken($token)){     // AUTH: token
            $id = self::snip($pid);
            self::logMsg("login: valid token $id");
            $this->setupSession($pid, $player->lang());
            $this->setupCookie($pid, $token);
        } else {
            $id = self::snip($pid);
            self::logMsg("login: invalid token pid=$id");
            $player=NULL;                           // AUTH: failure
        }

        return $player;
    }


    private function setupSession($pid, $lang){
        $_SESSION['pid'] = $pid;
        $_SESSION['lang'] = $lang;
    }


    private function setupCookie($pid, $token){
        if (!headers_sent()){
            setcookie("pid", $pid, time() + 3*Player::MONTH, "/");
            setcookie("token", $token, time() + 3*Player::MONTH, "/");
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

    public function selectLanguage($req, $player){
        $languages = self::translation()->languages();
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
        && (NULL !== $player->lang())
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
    Return the html template after replicating <r>elements</r> with data from $player & $req.

    $html    String        html template with variables of the form [key]
    $player    XML            player data
    $req    ArrayMap    url parameters from post&get
    ********************************************************************************/
    public function legacyReplicate($html, $player, $req){
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
                case 'friends':   return $this->replicateFriends($player, $html);      break;
                case 'ability':    return $this->replicateAbility($player, $html);       break;
                case 'reckon':     return $this->replicateReckons($player, $html);       break;
                case 'uploads':    return $this->replicateUploads($player, $html);       break;
                case 'translation':return $this->replicateTranslate($html);              break;
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
                        'name'  => $nam,
                        'value' => $val
                    );
                    $group .= $this->populate($html, $vars);
                }
            } elseif(isset($value)){
                $vars = array(
                    'name'  => $name,
                    'value' => $value
                );
                $group .= $this->populate($html, $vars);
            }
        }
        return $group;
    }


    public function replicateGames($html, $req){
        $default = $req['game'];
        $group = '';
        foreach(self::qwikGames() as $game => $name){
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
        $similar = array_slice($this->similarVenues($req['venue']), 0, 5);
        foreach($similar as $vid){
            try {
                $venue = new Venue($vid);
                $vars = array(
                    'vid'    => $vid,
                    'name'   => implode(', ',explode('|',$vid)),
                    'players'=> $venue->playerCount(),
                );
                $group .= $this->populate($html, $vars);
            } catch (RuntimeException $e){
                self::logThrown($e);
            }
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
                'game'      => self::qwikGames()[$game],
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

        $emails = array();
        $reckoning = $player->reckon("email");
        foreach($reckoning as $reckon){
            $emails[] = (string) $reckon['email'];
        }
        $emails = array_unique($emails);

        $playerVars = $this->playerVariables($player);
        foreach($emails as $email){
                $playerVars['email'] = $email;
                $group .= $this->populate($html, $playerVars);
        }
        return $group;
    }


    private function replicateFriends($player, $html){
        if(!$player){ return; }
        $group = '';
        $playerVars = $this->playerVariables($player);
        $reckoning = $player->reckon("rival");
        $emails = array();
        foreach($reckoning as $reckon){
            $email = (string) $reckon['email'];
            if (!array_key_exists($email, $emails)){
                $emails[$email] = TRUE;
                $parity = (int) $reckon['parity'];
                $game = $reckon['game'];
                $reckonVars = array(
                    'id'        => $reckon['id'],
                    'email'     => $email,
                    'game'      => self::qwikGames()["$game"],
                    'parity'    => self::parityStr($parity)
                );
                $vars = $playerVars + $reckonVars + self::$icons;
                $group .= $this->populate($html, $vars);
            }
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
                'game'      => self::qwikGames()["$game"],
                'ability'   => $abilities["$ability"]
            );
            $vars = $playerVars + $reckonVars + self::$icons;
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }


    private function replicateLanguages($html){
        $languages = self::translation()->languages();
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
            $status = $ranking->status();
            $vars = array(
                'status'   => $status,
                'fileName' => $ranking->fileName(),
                'crossAct' => $status == 'uploaded' ? 'delete' : 'deactivate',
                'tickIcon' => $status == 'uploaded' ? self::TICK_ICON : '',
                'title'    => $ranking->title(),
                'game'     => $ranking->game(),
                'time'     => $ranking->time()
            );
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }


    private function replicateTranslate($html){
        $group = '';
        $translation = self::translation();
        $pending = self::pending();
        if(!$translation || !$pending){ return; }
        $langs = $pending->languages();
        $keys = $pending->phraseKeys();
        foreach($keys as $key){
            $en_phrase = $translation->phrase($key, 'en');
            foreach($langs as $lang => $native){
                $phrase = $pending->phrase($key, $lang, '');
                if(isset($phrase)){
                    $translationVars = array(
                        'key'       => $key,
                        'en_phrase' => $en_phrase,
                        'lang'      => $lang,
                        'phrase'    => $phrase
                    );
                    $vars = $translationVars + self::$icons;
                    $group .= $this->populate($html, $vars);
                }
            }
        }
        return $group;
    }



    function playerVariables($player){
        return array(
            'target'    => 'match.php',
            'reputation'=> $this->repStr($player->repWord())
        );
    }



    public function datalists(){
        $datalists = '';
        foreach(self::qwikGames() as $game => $name){
            $datalists .= "\n\n" . $this->venueDatalist($game);
        }
        $datalists .= $this->countryDatalist();
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


    protected function countryDatalist(){
        $datalist = "<datalist id='country_iso'>\n";
        foreach(self::countries() as $val => $txt){
            $datalist .= "\t<option value='$val'>\n";
        }
        $datalist .= "</datalist>\n";
        return $datalist;
    }


    function gameOptions($game='squash', $tabs=''){
        if(empty($game)){
            $game='squash';
        }
        $options = '';
        foreach(self::qwikGames() as $val => $txt){
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
        $admin1s = array();
        $localities = array();
        foreach($available as $avail){
            $venueID = $avail->venue;
            $reg = explode('|', $venueID);
            $last = count($reg);
            if ($last >= 3){
                $countries[] = $reg[$last-1];
                $admin1s[] = $reg[$last-2];
                $localities[] = $reg[$last-3];
            } else {
                self::logMsg("warning: unable to extract region '$venueID'");
            }
        }

        $countries = array_unique($countries);
        $admin1s = array_unique($admin1s);
        $localities = array_unique($localities);

        sort($countries);
        sort($admin1s);
        sort($localities);

        return array_merge($countries, $admin1s, $localities);
    }


    function countryOptions($country, $tabs=''){
        if(!isset($country)){
            $country = Locate::geolocate('countryCode');
        }
        $options = '';
        foreach(self::countries() as $val => $txt){
            $selected = ($val == $country) ? " selected" : '';
            $options .= "$tabs<option value='$val' $selected>$txt</option>\n";
        }
        return $options;
    }


    function repStr($word){
        return empty($word) ? 'AAAAAA' : " with a $word reputation";
    }


    static public function parityStr($parity){
        if(is_numeric($parity)){
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
        return '{unknown parity}';
    }


    private function trim_value(&$value){
        $value = trim($value);
    }


    private function firstWordBold($phrase){
        $words = explode(' ', $phrase);
        $first = $words[0];
        $words[0] = "<b>$first</b>";
        return implode(' ', $words);
    }


    private function venueLink($vid){
        $name = explode("|", $vid)[0];
        $boldName = $this->firstWordBold($name);
        $url = self::QWIK_URL."/venue.php?vid=$vid";
        $link = "<a href='$url'>$boldName</a>";
        return $link;
    }


    static public function daySpan($hours, $day='', $clock24hr=FALSE){
        if (count($hours) == 0){
            return "";
        }

        $dayX = "<b>" . substr($day, 0, 3) . "</b>";
        if (count($hours) == 24){
            return "<span class='lolite'>$dayX</span>";
        }

        $str = '';
        if($clock24hr){
            $str = $dayX;
            $dayX = NULL;
        }
        $str .= ' ';
        $prior = $hours[0] - 1;
        foreach($hours as $hr){
             $lastChar = substr($str, strlen($str)-1, 1);
             $consecutive = $hr == ($prior + 1);
             if($consecutive){
                 $str .= $lastChar == ' ' ? self::clock($prior) : '' ;
                 $str .= '&middot';
             } else {
                 $str .= self::clock($prior) . ' ';
             }

            if ($hr > 12 && isset($dayX)) {
                $str .= $dayX;
                $dayX = NULL;
                $str .= $consecutive ? '&middot' : '' ;
            }

            $prior = $hr;
        }
        $str .= self::clock($prior);
        $str .= $dayX!=NULL ? " $dayX" : '';
        return "<span class='lolite'>$str</span>";
    }


    private function weekSpan($xml){
        $html = "";
        $hrs = $xml->xpath("hrs");
        foreach($hrs as $hr){
            $hours = new Hours($hr);
            $html .= self::daySpan($hours->roster(), $hr['day']);
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

}

Page::initStatic();

?>
