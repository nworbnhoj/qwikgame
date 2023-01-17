<?php 

require_once 'Qwik.php';
require_once 'Filter.php';


class Defend extends Qwik {
    
  const FILTER_ARGS = array(
    'admin'         => Filter::ADMIN,
    'avoidable'     => Filter::AVOID,
    'beckon'        => Filter::CHECKBOX,
    'notify-email'  => Filter::CHECKBOX,
    'notify-push'   => Filter::CHECKBOX,
    'time'          => Filter::CHECKBOX,
    'hour'          => Filter::HOURS,
    'Sun'           => Filter::HOURS,
    'Mon'           => Filter::HOURS,
    'Tue'           => Filter::HOURS,
    'Wed'           => Filter::HOURS,
    'Thu'           => Filter::HOURS,
    'Fri'           => Filter::HOURS,
    'Sat'           => Filter::HOURS,
    'smtwtfs'       => Filter::HOURS,
    'today'         => Filter::HOURS,
    'tomorrow'      => Filter::HOURS,
    'html'          => Filter::HTML,
    'game'          => Filter::GAME,
    'honeypot'      => Filter::HONEYPOT,
    'id'            => Filter::ID,
    'invite'        => Filter::INVITE,
    'lang'          => Filter::LANG,
    'account-lang'  => Filter::LANG,
    'lat'           => Filter::LAT,
    'lng'           => Filter::LNG,
    'name'          => Filter::VENUENAME,
    'msg'           => Filter::MSG,
    'nick'          => Filter::NAME,
    'parity'        => Filter::PARITY,
    'pid'           => Filter::PID,
    'placeid'       => Filter::PLACEID,
    'push-key'      => Filter::PUSHKEY,
    'push-token'    => Filter::PUSHTOK,
    'qwik'          => Filter::QWIK,
    'region'        => Filter::REGION,
    'title'         => Filter::NAME,
    'token'         => Filter::TOKEN,
    'rep'           => Filter::REP,
    'vid'           => Filter::VID,
    'delay'         => FILTER_VALIDATE_INT,
    'account-email' => FILTER_VALIDATE_EMAIL,
    'email'         => FILTER_VALIDATE_EMAIL,
    'rival'         => FILTER_VALIDATE_EMAIL,
    'url'           => FILTER_VALIDATE_URL,
    'push-endpoint' => FILTER_VALIDATE_URL,
    
    'key'           => FILTER_DEFAULT,
    'phrase'        => FILTER_DEFAULT
  );
  

  const GET_KEYS = array('html', 'pid', 'game', 'country_iso', 'lang', 'register', 'vid', 'token', 'qwik', 'email','lat', 'lng', 'avoidable', 'region', 'id');
  
  
  const QWIK_KEYS = array(
    'accept'    => array('qwik', 'id', 'hour', 'delay'),
    'account'   => array('qwik', 'nick', 'url', 'email', 'lang', 'notify-email', 'push-endpoint', 'notify-push', 'push-token', 'push-key'),
    'activate'  => array('qwik', 'id'),
    'available' => array('qwik', 'pid', 'token', 'email', 'vid', 'game', 'parity', 'smtwtfs', 'lat', 'lng', 'placeid', 'Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'time'),
    'book'      => array('qwik', 'id', 'book', 'call'),
    'cancel'    => array('qwik', 'id', 'hour', 'delay'),
    'deactivate'=> array('qwik', 'id'),
    'decline'   => array('qwik', 'id'),
    'delete'    => array('qwik', 'id', 'delay'),
    'facility'  => array('qwik', 'pid', 'token', 'email', 'vid', 'game', 'Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'today', 'tomorrow'),
    'feedback'  => array('qwik', 'id', 'rep', 'parity'),
    'friend'    => array('qwik', 'game', 'rival', 'parity'),
    'keen'      => array('qwik', 'vid', 'game', 'today', 'tomorrow', 'beckon', 'invite', 'lat', 'lng', 'placeid'),
    'login'     => array('qwik', 'pid', 'token'),
    'logout'    => array('qwik', 'push-endpoint'),
    'msg'       => array('qwik', 'msg', 'id'),
    'recover'   => array('qwik', 'email'),
    'region'    => array('qwik', 'game', 'parity', 'region'),
    'register'  => array('qwik', 'email'),
    'translate' => array('qwik', 'key', 'phrase'),
    'undo'      => array('qwik', 'id', 'hour'),
    'upload'    => array('qwik', 'id', 'game', 'title'),
    'quit'      => array('qwik')
  );
    


    static function xml($url){
        try{
            $reply = file_get_contents("$url");
            $tidy = tidy_parse_string($reply, self::TIDY_CONFIG, 'utf8');
            $tidy->cleanRepair();
            $clean = tidy_get_output($tidy);
            return new SimpleXMLElement($clean);
        } catch (Exception $e){
            $msg = $e->getMessage();
            $url = self::logSafe($url);
            $reply = self::logSafe($reply);
            self::logMsg("SimpleXML: $msg\n$url\n$reply");
        }
        return new SimpleXMLElement("<xml></xml>");
    }


    static function json($url){
        try{
            $json = file_get_contents("$url");
            $decoded = json_decode($json, TRUE);
            $clean = self::declaw($decoded);            
            return json_encode($clean);
        } catch (Exception $e){
            $msg = $e->getMessage();
            $url = self::logSafe($url);
            $json = self::logSafe($json);
            self::logMsg("JSON: $msg\n$url\n$json");
        }
        return "{}";
    }



    # SECURITY escape all parameters to prevent malicious code insertion
    # http://au.php.net/manual/en/function.htmlentities.php
    static private function declaw($cat){
        if (is_array($cat)){
            $kitten = array();
            foreach($cat as $key => $val){
                $kitten[self::declaw($key)] = self::declaw($val);
            }
        } else if(isset($cat)) {
            $kitten = htmlspecialchars(trim($cat), ENT_QUOTES | ENT_HTML5, "UTF-8");
        } else {
          $kitten = '';
        }
        return $kitten;
    }


  /****************************************************************************
   * Sanitizes an untrusted string for entry into a log.
   * Malicious input written to a log can severly disrupt the log itself.
   * Log-forgery typically requires numerous newlines or carriage returns.
   * @param hot an untrusted string or Array[Array[String]]
   * @return a sanitized string safe to be written to log 
   ***************************************************************************/
  static function logSafe($hot){
    if (is_array($hot)){
      $cool = array();
      foreach($hot as $key => $val){
        $cool[self::logSafe($key)] = self::logSafe($val);
      }
    } else {
      $flags = FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH;
      $cool = filter_var($hot, FILTER_SANITIZE_STRING, $flags);
      $cool = substr($cool, 0, 1000);
    }
    return $cool;
  }


    private $get;
    private $post;
    private $query;
    private $rejected = array();
    private $rejectReason = '';


    /**************************************************************************
     * @param honeypot an array mapping index of spambot honeypots to the index
     *                 of the valid human data 
     *************************************************************************/
    public function __construct($honeypot=array()){
      parent::__construct();
      $this->checkHoneypot($_GET, $honeypot);
      $this->checkHoneypot($_POST, $honeypot);
      $this->query();
    }
    
    
    /**************************************************************************
     * Checks for any data in $_POST & $GET at the honeypot-indices. Any such
     * captured data is moved into $_POST[honeypot] - and the authentic human
     * data at the human-indices are relocated back into the correct index
     * @param honeypot an array mapping index of spambot honeypots to the index
     *                 of the valid human data 
     *************************************************************************/
    private function checkHoneypot(&$req, $honeypot){
      foreach($honeypot as $bot => $human){
        if (!empty($req[$bot])){
          if(!isset($req['honeypot'])){ $req['honeypot'] = '';  }   // initialize
          $req['honeypot'] .= "$bot=".$req[$bot]."  ";           // log honeypot
        }
        if(isset($req[$human])){
          $req[$bot] = $req[$human];  // relocate human data
          unset($req[$human]);
        }
      }
    }


    public function get(){
      if (is_null($this->get)){
        if ($this->validateKeys($_GET, self::GET_KEYS)){
          $this->get = $this->examine($_GET);
        } else {
          $this->get = array();
        }
      }
      return $this->get;
    }


    public function post(){
      if (is_null($this->post)){
        if (isset($_POST['qwik'])
        && isset(self::QWIK_KEYS[$_POST['qwik']])
        && $this->validateKeys($_POST, self::QWIK_KEYS[$_POST['qwik']])){
          $this->post = $this->examine($_POST);
        } else {
          $this->post = array();
        }
      }
      return $this->post;
    }


    private function query(){
      if(!isset($this->query)){
        $this->query = $this->post() + $this->get();
        $this->logRejected();
      }
      return $this->query;
    }
    
    
    public function param($key=NULL, $value=NULL){
      if(is_null($key)){
        return $this->query;
      } elseif (is_null($value) && isset($this->query[$key])){
        return $this->query[$key];
      } else {
        $this->query[$key] = $value;
      }
      return NULL;    
    }
    
    
    public function unset($key){
      unset($_GET[$key]);
      unset($_POST[$key]);
      unset($this->get[$key]);
      unset($this->post[$key]);
    }


    public function rejected(){
        if (is_null($this->get) && is_null($this->post)){
            $this->query();
        }
        return $this->rejected;
    }


    public function logRejected(){
      $rejected = $this->rejected();
      if(!empty($rejected)){
        $reason = self::logSafe($this->rejectReason);
        $reject = print_r(self::logSafe($rejected), TRUE);
        self::logMsg("Defend $reason: $reject");
      }
    }


    private function examine($request){
      $result = filter_var_array($request, self::FILTER_ARGS, FALSE);
      if ($this->size($result) !== $this->size($request)){
        $this->rejected = $this->rejected + $this->rejects($request, $result);
      }
      return $result;
    }
    
    
  /****************************************************************************
   * Rejects any input request containing 1 or more invalid keys.
   * @param $request the input Array to have keys validated
   * @return true if all $request keys are valid keys 
   ***************************************************************************/
  private function validateKeys($request, $allowed){
    foreach($request as $key => $value){
      if(!in_array($key, $allowed)){    // request includes invalid key
        $this->rejected = $request;
        $this->rejectReason = "invalid key [$key]";
        return false;                         // reject outright
      }
    }
    return true;                              // all valid keys
  }


    private function rejects($raw, $safe){
        $rejects = NULL;
        if(is_array($raw)){
            $rejects = array();
            foreach($raw as $rawKey => $rawVal){
                $badVal = NULL;
                if (array_key_exists($rawKey, $safe)){  // recursion
                    $badVal = $this->rejects($rawVal, $safe[$rawKey]);
                } else {
                    $badVal = print_r($rawVal, TRUE);
                }
                if(!empty($badVal)){
                    $rejects[$rawKey] = $badVal;
                }
            }
        } elseif($raw != $safe) {    // weak test as some filters convert type
            $rejects = print_r($raw, TRUE);
        }
        return empty($rejects) ? [] : $rejects;
    }


    /*********************************************************
    *
    * returns the number of non-empty data (ie #keys + #value)
    **********************************************************/
    private function size($data){
        $size = empty($data) ? 0 : 1;
        if(is_array($data)){
            $size += count(array_keys($data));
            foreach($data as $value){
                $size += $this->size($value);
            }
        }
        return $size;
    }
}

?>
