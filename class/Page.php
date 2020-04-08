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
    const GITHUB_ICON    = 'fa fa-github icon';
    
    const FLYER_URL    = QWIK_URL.'/pdf/qwikgame.org%20flyer.pdf';
    const FACEBOOK_URL = 'https://www.facebook.com/sharer/sharer.php?u='.QWIK_URL;
    const TWITTER_URL  = 'https://twitter.com/intent/tweet?text={tagline}&url='.QWIK_URL;
    const GITHUB_URL   = 'https://github.com/nworbnhoj/qwikgame#readme';

    const EMAIL_IMG    = "<img src='img/email.png' alt='email' class='socialmedia'>";
    const FACEBOOK_IMG = "<img src='img/facebook.png' alt='facebook' class='socialmedia'>";
    const TWITTER_IMG  = "<img src='img/twitter.png' alt='twitter' class='socialmedia'>";
    const GITHUB_IMG   = "<img src='img/GitHub.png' alt='github' class='socialmedia'>";

    const EMAIL_LNK    = "<a href='mailto:?subject=".QWIK_URL."&body=".QWIK_URL."%20makes%20it%20easy%20to%20{tagline}&target=_blank'>".self::EMAIL_IMG."</a>";
    const FACEBOOK_LNK = "<a href='".self::FACEBOOK_URL."' target='_blank'>".self::FACEBOOK_IMG."</a>";
    const FLYER_LNK    = "<a href='".self::FLYER_URL."' target='_blank'>{flyer}</a>";
    const TWITTER_LNK  = "<a href='".self::TWITTER_URL."' target='_blank'>".self::TWITTER_IMG."</a>";
    const GITHUB_LNK   = "<a href='".self::GITHUB_URL."' target='_blank'>".self::GITHUB_IMG."</a>";

    static $icons;


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
            $str = $dayX." ";
            $dayX = NULL;
        }

        $start = $hours[0];
        $mid = '';
        $end = $start;
        $str .= Qwik::clock($start);
        foreach($hours as $hr){
            if($hr > $end+1){  // run has finished
                $str .= $end==$start ? " " : $mid.Qwik::clock($end)." ";
                $start = $hr;
                $mid='';
                $end = $start;
                $str .= Qwik::clock($start);
            } else {
                $mid .= '&middot';
                $end = $hr;
            }
            if ($hr > 12 && isset($dayX)) {
                $mid .= $dayX;
                $dayX = NULL;
            }
        }
        $str .= $end==$start ? "" : $mid.Qwik::clock($end);

        return "<span class='lolite'>$str</span>";
    }


    private $player;
    private $language;
    private $req;
    private $alert = "";
    private $msg = "";


    /*******************************************************************************
    Class Page is constructed with the name of the file containing a html template.

    $templateName  String  fileName containing the html template.
    *******************************************************************************/
    public function __construct($template, $templateName=NULL){

        $defend = new Defend();
        $this->req = $defend->request();

//        $this->logReq($this->req);
        $this->player = $this->login($this->req);

        $language = $this->selectLanguage($this->req, $this->player);

        $template = empty($template) ? Html::readTemplate($templateName, $language) : $template;
        
        parent::__construct($template, $language);
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
        $vars['GITHUB_ICON']   = self::GITHUB_ICON;
        $vars['flyerLink']     = self::FLYER_LNK;
        $vars['thumb-up'] = "<span class='" . self::THUMB_UP_ICON . "'></span>";
        $vars['thumb-dn'] = "<span class='" . self::THUMB_DN_ICON . "'></span>";
        $game = (string) $this->req('game');
        $vars['game']  = empty($game) ? '[game]' : self::gameName($game);
        
        if ($this->player != NULL){
            $vars['pid']         = $this->player->id();
        }

        $vars['alert-hidden'] = empty($this->alert) ? 'hidden' : '';
        $vars['alert']        = $this->alert;
        $vars['msg-hidden']   = empty($this->msg) ? 'hidden' : '';
        $vars['message']      = $this->msg;
        
        return $vars;
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
                case  'html':
                    $html = html_entity_decode($val);
                    $matches = array();
                    preg_match("/<(.*?)>/", $html, $matches);
                    $msg .= $matches[0];
                break;

                default:
                    $msg .= is_array($val) ? print_r($val, true) : $val;
            }
        }
        self::log()->lwrite($msg);
        self::log()->lclose();
    }


    function alert($txt){
        $this->alert = empty($this->alert) ? $txt : $this->alert."<br>$txt";
    }


    function message($txt){
        $this->msg = empty($this->msg) ? $txt : $this->msg."<br>$txt";
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
            $id = self::snip($pid);
            self::logMsg("logout $id");
            unset($_SESSION['pid']);
            unset($_COOKIE['pid']);
            unset($this->player);
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
            echo("Please click on this link: <a href='".QWIK_URL."'>this link</a>");
        } else {
            header("location: ".QWIK_URL);
        }
    }


    /********************************************************************************
    Return the current player language or default

    $req    ArrayMap    url parameters from post&get
    $player    XML            player data
    ********************************************************************************/

    public function selectLanguage($req, $player){
        $languages = parent::$phraseBook->languages();
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


    function playerVariables($player){
        return array(
            'target'    => 'match.php',
            'reputation'=> $this->repStr(isset($player) ? $player->repWord() : '')
        );
    }


    function gameOptions($game='squash', $tabs=''){
        $game = empty($game) ? 'squash' : $game ;
        $options = '';
        foreach(self::qwikGames() as $val => $txt){
            $selected = ($val === $game) ? 'selected' : '';
            $options .= "$tabs<option value='$val' $selected>$txt</option>\n";
        }
        return $options;
    }


    function languageOptions($language='english'){
        $game = empty($language) ? 'en' : $language ;
        $options = '';
        foreach(self::$phraseBook->languages() as $key => $val){
            $selected = ($key === $language) ? 'selected' : '';
            $options .= "<option value='$key' $selected>$val</option>\n";
        }
        return $options;
    }
    
    
    public function regions(){
        $player = $this->player();
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


    public function venueLink($vid){
        $name = explode("|", $vid)[0];
        $boldName = $this->firstWordBold($name);
        $url = QWIK_URL."venue.php?vid=$vid";
        $link = "<a href='$url'>$boldName</a>";
        return $link;
    }

    
    function similarVenues($description, $game=NULL){
        $description = trim($description);
        if (strlen($description) == 0) { return array(); }

        $words = explode(" ", $description, 5);
        foreach($words as $key=>$val){
            $word = trim($val);
            if (strlen($word) > 2){
                $words[$key] = $word;
            } else {
                unset($words[$key]);
            }
        }
        if (sizeof($words) == 0) { return array(); }

        $similar = array();
        $existingVenues = self::venues($game);
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
