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
    const BOOKING_ICON   = 'fa fa-book icon';
    const COMMENT_ICON   = 'fa fa-comment-o comment';
    const CROSS_ICON     = 'fa fa-times-circle cross';
    const EMAIL_ICON     = 'fa fa-envelope-o icon';
    const FACEBOOK_ICON  = 'fa fa-facebook icon';
    const FACILITY_ICON  = 'fa fa-clock-o icon';
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
    

    const TRANSLATE_URL= QWIK_URL.'translate.php';
    const TRANSLATE_LNK= "<a href='".self::TRANSLATE_URL."' target='_blank'>{translate}</a>";

    const TWITTER_URL  = "https://twitter.com/qwikgame'";
    const TWITTER_IMG  = "<img src='img/twitter.png' alt='twitter' class='socialmedia'>";
    const TWITTER_LNK  = "<a class='fa fa-twitter twitter' href='".self::TWITTER_URL."' target='_blank'></a>";

    const FORUM_URL  = "https://forum.qwikgame.org/";
    const FORUM_IMG  = "";
    const FORUM_LNK  = "<a href='".self::FORUM_URL."' target='_blank'>{forum}</a>";

    const GITHUB_URL   = "https://github.com/nworbnhoj/qwikgame#readme";
    const GITHUB_IMG   = "<img src='img/GitHub.png' alt='github' class='socialmedia'>";
    const GITHUB_LNK   = "<a href='".self::GITHUB_URL."' target='_blank'>".self::GITHUB_IMG."</a>";

    const FACEBOOK_URL = "https://www.facebook.com/sharer/sharer.php?u=".QWIK_URL;
    const FACEBOOK_IMG = "<img src='img/facebook.png' alt='facebook' class='socialmedia'>";
    const FACEBOOK_LNK = "<a href='".self::FACEBOOK_URL."' target='_blank'>".self::FACEBOOK_IMG."</a>";

    const TWEET_URL    = "https://twitter.com/intent/tweet?text={tagline}&url=".QWIK_URL;
    const TWEET_LNK    = "<a href='".self::TWEET_URL."' target='_blank'>".self::TWITTER_IMG."</a>";

    const EMAIL_URL    = "mailto:?subject=".QWIK_URL."&body=".QWIK_URL."%20makes%20it%20easy%20to%20{tagline}&target=_blank";
    const EMAIL_IMG    = "<img src='img/email.png' alt='email' class='socialmedia'>";
    const EMAIL_LNK    = "<a href='".self::EMAIL_URL."'>".self::EMAIL_IMG."</a>";

    const WEEKDAYS = array('Mon'=>'0','Tue'=>'0','Wed'=>'0','Thu'=>'0','Fri'=>'0','Sat'=>'0','Sun'=>'0');
    const TWODAYS = array('today'=>'0','tomorrow'=>'0');


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


    static public function weekSpan($xml){
        $html = "";
        $hrs = $xml->xpath("hrs");
        foreach($hrs as $hr){
            $day = (string) $hr['day'];
            if (array_key_exists($day, self::WEEKDAYS)) {
                $hours = new Hours($hr);
                $html .= self::daySpan($hours->roster(), $day);
            }
        }
        return $html;
    }




    static public function hourRows($days){
        $hourRows = '';
        $tabs = "\t\t\t\t";
        foreach($days as $day => $bits){
            $bit = 1;
            $hrs = 0;
            $hours = new Hours($bits);
            $hourRows .= "$tabs<tr id='$day'>\n";
            $hourRows .= "$tabs\t<th class='tr-toggle'>{$day}</th>\n";
            for($hr24=0; $hr24<=23; $hr24++){
                if (($hr24 < 6) | ($hr24 > 20)){
                    $hidden = 'hidden';
                } else {
                    $hidden = '';
                }
                $on = $hours->get($hr24) ? 1 : 0;
                if ($on == 1) {
                    $hrs += $bit;
                }
                if ($hr24 <= 12){
                    $hr12 = $hr24;
                } else {
                    $hr12 = $hr24-12;
                }
                $hourRows .= "$tabs\t<td class='toggle' on='$on' bit='$bit' hr='$hr24' $hidden>$hr12</td>\n";
                $bit = $bit * 2;
            }
            $hourRows .= "$tabs\t<input name='$day' type='hidden' value='$hrs'>\n";
            $hourRows .= "$tabs</tr>\n";
        }
        return $hourRows;
    }


    private $user;
    private $language;
    private $query;
    private $alert = "";
    private $msg = "";


    /*******************************************************************************
    Class Page is constructed with the name of the file containing a html template.

    $templateName  String  fileName containing the html template.
    *******************************************************************************/
    public function __construct($template, $templateName=NULL, $honeypot=array()){

        $this->query = new Defend($honeypot);
        $this->user = $this->login($this->query);
        $language = $this->selectLanguage($this->query->param('lang'), $this->user);

        $template = empty($template) ? Html::readTemplate($templateName, $language) : $template;
        
        parent::__construct($template, $language);
    }


    public function serve($history = NULL){
        try {
            if(empty($history)
            && $this->qwik()){
              $history = basename($_SERVER["SCRIPT_FILENAME"]);
            }
            $this->processRequest();
        } catch (Throwable $t){
            Qwik::logThrown($t);
        } finally {
            parent::serve($history);
        }
    }



    /**************************************************************************
     * Completes processing before serving the page
     * Implements a generic delay/undo function. A query including a delay
     * parameter is captured for delayed resubmission unless a qwik=undo
     * request is received in the interim.
     *************************************************************************/
    public function processRequest(){
        $result = NULL;
        $user = $this->user();
        if(isset($user)){
            $uid = $user->id();
            $sid = self::snip($uid);
            $request = json_encode($this->req());
            self::logMsg("$sid $request");

            if ($this->delayed($this->req('delay'))){                // delay request
                http_response_code(204);                               // no content
                exit;      
            }

            switch ($this->req('qwik')) {
                case 'undo':                     // undo delayed request
                    $result = $this->qwikUndo($uid, $this->req('id'));
                    break;
                case 'register':
                    $result = $this->qwikRegister($this->req('email'));
                    break;
                default:
            }  
        }    
        return $result;
    }
    
    
    /**************************************************************************
     * Captures the current request (target, query, session, get & post) for
     * resubmission after a delay.
     * Resubmission may be completed by cron job resubmit.php
     * @param  $delay The minimum delay in seconds before resubmission.
     * @return        An id for the delayed request
     *************************************************************************/
    private function delayed($delay = null){
      if(!is_numeric($delay)
      || !isset($_SERVER["PHP_SELF"])){
        return FALSE;
      }
      
      $get  = $this->query->get();
      unset($get['delay']);                             // prevent endless loop
      $post = $this->query->post();
      unset($post['delay']);                            // prevent endless loop
      $task = array(                                    // capture task details
        'due'     => time() + (int)$delay,
        'target'  => $_SERVER["PHP_SELF"],
        'get'     => $get,
        'post'    => $post,
        'session' => $_SESSION
      );        
      $json = json_encode($task);
      $id = $this->req('id');
      $id = empty($id) ? hrtime() : $id;
      $this->writeFile($json, PATH_DELAYED, $id);      // save for resubmission

      return $id;
    }


    /**************************************************************************
     * Cancels a previously delayed request awaiting resubmission.
     * Resubmission may be completed by cron job resubmit.php
     * @param  $uid The unique user-id
     * @param  $id  The unique request-id
     * @return      The request-id on success (or null otherwise)
     *************************************************************************/
    function qwikUndo($uid, $id){
      if(!isset($uid)
      || !isset($id)
      || !is_file(PATH_DELAYED.$id)){
        return NULL;
      }
      
      $json = file_get_contents(PATH_DELAYED.$id);             // retrieve task
      if($json){
        $task = json_decode($json, TRUE);
        $session = $task['session'];
        if(isset($session['pid'])                         // check user match
        && $uid === $session['pid']){
         unlink(PATH_DELAYED.$id);                       // remove delayed task
        }
        return $id;
      }
    }


    function qwikRegister($email){
        $user = $this->user();
        if(isset($user)
        && isset($email)
        && empty($user->email())){
            $user->email($email);
        }
    }


    public function variables(){
        $vars = parent::variables();
        $vars['thumb-up'] = "<span class='" . self::THUMB_UP_ICON . "'></span>";
        $vars['thumb-dn'] = "<span class='" . self::THUMB_DN_ICON . "'></span>";
        $game = (string) $this->query->param('game');
        $vars['game']  = empty($game) ? '[game]' : self::gameName($game);
        
        if ($this->user != NULL){
            $vars['pid']         = $this->user->id();
        }

        $vars['alert-hidden'] = empty($this->alert) ? 'hidden' : '';
        $vars['alert']        = $this->alert;
        $vars['msg-hidden']   = empty($this->msg) ? 'hidden' : '';
        $vars['message']      = $this->msg;
        $vars['menu']         = $this->menu();
        return $vars;
    }


    protected function menu(){
        $html = '';
        $user = $this->user;
        if (isset($user)){
            $playerIcons = $user->isPlayer() ? Player::PLAYER_ICONS : array();
            $managerIcons = $user->isManager() ? Manager::MANAGER_ICONS : array();
            $icons = $playerIcons + $managerIcons + User::USER_ICONS;
            foreach($icons as $key => $icon ){
                $html .= "        <a id='$key-icon' class='$icon' href='$key.php'></a>\n";
            }
        }
        return $html;
    }


    public function user(){
        return $this->user;
    }

    public function player(){
        if (isset($this->user) && get_class($this->user) == "Player"){
            return $this->user;
        }
        return NULL;
    }


    public function manager(){
        if (isset($this->user) && get_class($this->user) == "Manager"){
            return $this->user;
        }
        return NULL;
    }


    public function req($key=NULL, $value=NULL){
      return $this->query->param($key, $value);
    }


    public function qwik(){
      return $this->query->param('qwik');
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



    /**************************************************************************
     * Attempts to identify and authenticate a Player.
     * The Player identification (pid) can be obtained (in priority order)
     * from the:
     * 1. query[pid] overrides an existing open PHP Session 
     * 2. session[pid] an already authenticated Player ID
     * 3. cookie[pid] the long term storage of an authenticated Player ID
     * 4. query-email a new Player registration.
     * Next, Authentication is completed if the supplied token is valid
     * Finally the session[pid], cookie[pid] and cookie[token] are setup
     * $req    ArrayMap    url parameters from post&get
     * @return Player The authenticated Player object or NULL otherwise
     *************************************************************************/
    private function login($query){
        if (session_status() == PHP_SESSION_NONE
        && !headers_sent()) {
            session_start();
        }

        $openSession = false;
        // Locate identification (pid) and authentication (token) if they exist
        if ($query->param('token')
        && $query->param('pid')){           // check in the request
          $uid = $query->param('pid');
          $token = $query->param('token');
          $query->unset('token');   // rely on open session from here
        } elseif (isset($_SESSION['pid'])){ // check in the $_SESSION variable
          $uid = $_SESSION['pid'];
          $openSession = true;
        } elseif (isset($_COOKIE['pid'])){  // check in a $_COOKIE
          $uid = $_COOKIE['pid'];
          $token = $_COOKIE['token'];
        } elseif ($query->param('email')){    // check for an email in the request
          $email = $query->param('email');
          $uid = Player::anonID($email);  // and derive the pid from the email
          $token = $query->param('token');
        } else {                            // anonymous session: no user identifier
           return;                         // RETURN login fail
        }

        // Load up the Player from file
        try {
            $user = $this::loadUser($uid);
            if(!$user->ok()){
              $sid = self::snip($uid);
              self::logMsg("user not OK $sid");
              return;                       // RETURN login fail
            }
        } catch (RuntimeException $e){
            self::logThrown($e);
            return;                         // RETURN login fail
        }

        // return the Player iff authentication is possible
        if($openSession){                   // AUTH: existing session
        } elseif($user->isValidToken($token)){     // AUTH: token
            $this->setupSession($uid, $user->lang());
            $this->setupCookie($user);
        } else {
            $sid = self::snip($uid);
            self::logMsg("token invalid $sid $token");
            return;                         // RETURN authentication failure
        }

        return $user;
    }


    protected function loadUser($uid){    
        return new User($uid, TRUE);
    }


    private function setupSession($uid, $lang){
        session_regenerate_id();
        $_SESSION['pid'] = $uid;
        $_SESSION['lang'] = $lang;
    }


    private function setupCookie($user){
      if (!headers_sent()){
        $uid = $user->id();
        $term = 3*self::MONTH;
        $token = $user->token($term);
        setcookie('pid', $uid, time() + $term, '/', HOST, TRUE, TRUE);
        setcookie('token', $token, time() + $term, '/', HOST, TRUE, TRUE);
      }
    }


    /********************************************************************************
    Logout the current user by deleting both the $_SESSION and the longer term
    $_COOKIE
    ********************************************************************************/
    public function logout(){
        $uid = "NULL";
        if (isset($_COOKIE) && isset($_COOKIE['pid'])){
            $uid = $_COOKIE['pid'];
            unset($_COOKIE['pid']);
            unset($_COOKIE['token']);
        }
        if (isset($_SESSION) && isset($_SESSION['pid'])){
            $uid = $_SESSION['pid'];            
            unset($_SESSION['pid']);
        }
        if (isset($this->user)){
            $uid = $this->user->id();
            $this->user = NULL;            
        }
        $id = self::snip($uid);
        self::logMsg("logout $id");        
        
        if (!headers_sent()){
            setcookie('pid', '', time() - Player::DAY, '/', HOST, TRUE, TRUE);
            setcookie('token', '', time() - Player::DAY, '/', HOST, TRUE, TRUE);
        }
        $this->goHome();
    }


    public function goHome(){
        if (headers_sent()){
            echo("Redirect failed.<br>");
            echo("Please click on <a href='".QWIK_URL."'>this link</a>");
        } else {
            header("Location: ".QWIK_URL, TRUE, 307);
            exit;
        }
    }


    /********************************************************************************
    Return the current user language or default

    $req    ArrayMap    url parameters from post&get
    $user    XML            user data
    ********************************************************************************/

    public function selectLanguage($lang, $user){
        $languages = parent::$phraseBook->languages();

        if(isset($lang)                            // REQUESTED language
        && isset($user)){
          $user->lang($lang);
          $user->save();
        } elseif (isset($_SESSION['lang'])){
            $lang = $_SESSION['lang'];
        } elseif (isset($user)                         // USER language
        && (NULL !== $user->lang())){
            $lang = (string) $user->lang();
        } elseif (false){                                // geolocate language
            // todo code
        } else {                                        // default english
            $lang = 'en';
        }

        $_SESSION['lang'] = $lang;
        return $lang;
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


    function languageOptions($language='en'){
        $lang = empty($language) ? 'en' : $language ;
        $options = '';
        foreach(self::$phraseBook->languages() as $key => $val){
            $selected = ($key === $lang) ? 'selected' : '';
            $options .= "<option value='$key' $selected>$val</option>\n";
        }
        return $options;
    }


    private function trim_value(&$value){
        $value = isset($value) ? trim($value) : '' ;
    }


    private function firstWordBold($phrase){
        $words = explode(' ', $phrase);
        $first = $words[0];
        $words[0] = "<b>$first</b>";
        return implode(' ', $words);
    }


    public function venueLink($vid, $game){
      $venue = new Venue($vid);
      $name = $venue->name();
      $boldName = $this->firstWordBold($name);
      $icon = self::MAP_ICON;
      $url = $venue->url();
      $lat = $venue->lat();
      $lng = $venue->lng();
      $num = $venue->playerCount();
      $fn = "clickMapIcon(event)";
      $venueLink = empty($url) ? $boldName : "<a href='$url' target='_blank'>$boldName</a>";
      $mapIcon = "<span class='$icon' data-vid='$vid' data-lat='$lat' data-lng='$lng' data-num='$num' data-game='$game' onclick='$fn'></span>";
      return "$venueLink $mapIcon";
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

?>
