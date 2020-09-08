<?php 

require_once 'Qwik.php';
require_once 'Filter.php';


class Defend extends Qwik {

  // An array of maximum string lengths. Used by: clip()
  const CLIP = array(
    'description' => 200,
    'filename'    => 50,
    'html'        => 2000,
    'key'         => 30,
    'msg'         => 200,
    'lang'        => 2,
    'name'        => 100,
    'nick'        => 20,
    'phrase'      => 2000,
    'push-key'    => 88,
    'push-token'  => 24,
    'region'      => 100,
    'venue'       => 150
  );


    
  const FILTER_ARGS = array(
    'admin'         => Filter::ADMIN,
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
    'game'          => Filter::GAME,
    'honeypot'      => Filter::HONEYPOT,
    'id'            => Filter::ID,
    'invite'        => Filter::INVITE,
    'lang'          => Filter::LANG, 
    'account-lang'  => Filter::LANG, 
    'lat'           => Filter::LAT,
    'lng'           => Filter::LNG,
    'parity'        => Filter::PARITY,
    'pid'           => Filter::PID,
    'placeid'       => Filter::PLACEID,
    'push-key'      => Filter::PUSHKEY,
    'push-token'    => Filter::PUSHTOK,
    'qwik'          => Filter::QWIK,
    'token'         => Filter::TOKEN,
    'rep'           => Filter::REP,        
           
    'avoidable'     => FILTER_SANITIZE_SPECIAL_CHARS,
    'filename'      => FILTER_SANITIZE_SPECIAL_CHARS,
    'html'          => FILTER_SANITIZE_SPECIAL_CHARS,
    'msg'           => FILTER_SANITIZE_SPECIAL_CHARS,
    'name'          => FILTER_SANITIZE_SPECIAL_CHARS,
    'nick'          => FILTER_SANITIZE_SPECIAL_CHARS,
    'region'        => FILTER_SANITIZE_SPECIAL_CHARS,
    'title'         => FILTER_SANITIZE_SPECIAL_CHARS,
    'venue'         => FILTER_SANITIZE_SPECIAL_CHARS,
    'vid'           => FILTER_SANITIZE_SPECIAL_CHARS,
    'account-email' => FILTER_VALIDATE_EMAIL, 
    'email'         => FILTER_VALIDATE_EMAIL,
    'rival'         => FILTER_VALIDATE_EMAIL,
    'account-url'   => FILTER_VALIDATE_URL,
    'push-endpoint' => FILTER_VALIDATE_URL,

    'account'       => FILTER_DEFAULT,
    'key'           => FILTER_DEFAULT,
    'phrase'        => FILTER_DEFAULT

//    'country'       => Filter::COUNTRY,    
//    'phone'         => Filter::PHONE,
//    'str-num'       => Filter::STR_NUM,
//    'admin1'        => FILTER_DEFAULT,
//    'address'       => FILTER_DEFAULT,
//    'input'         => FILTER_DEFAULT,
//    'locality'      => FILTER_DEFAULT,
//    'message'       => FILTER_DEFAULT,
//    'note'          => FILTER_DEFAULT,
//    'reply'         => FILTER_DEFAULT,
//    'route'         => FILTER_DEFAULT,
//    'skip'          => FILTER_DEFAULT,
//    'tz'            => FILTER_DEFAULT,
//    'account-notify'=> FILTER_DEFAULT, 
//    'url'           => FILTER_DEFAULT, //  FILTER_VALIDATE_URL,
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
        } else {
            $kitten = htmlspecialchars(trim($cat), ENT_QUOTES | ENT_HTML5, "UTF-8");
        }
        return $kitten;
    }


    private $get;
    private $post;
    private $rejected = array();


    /**************************************************************************
     * @param honeypot an array mapping index of spambot honeypots to the index
     *                 of the valid human data 
     *************************************************************************/
    public function __construct($honeypot=array()){
      parent::__construct();
      $this->checkHoneypot($_GET, $honeypot);
      $this->checkHoneypot($_POST, $honeypot);
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
        
        $req[$bot] = isset($req[$human]) ? $req[$human] : '';  // relocate human data
      }
    }


    public function get(){
        if (is_null($this->get)){
            $this->get = $this->examine($_GET);
        }
        return $this->get;
    }


    public function post(){
        if (is_null($this->post)){
            $this->post = $this->examine($_POST);
        }
        return $this->post;
    }


    public function request(){
       $request = $this->post() + $this->get();
       $this->logRejected();
       return $request;
    }


    public function rejected(){
        if (is_null($this->get) && is_null($this->post)){
            $this->request();
        }
        return $this->rejected;
    }


    public function logRejected(){
        $rejected = $this->rejected();
        if(!empty($rejected)){
            $reject = print_r($rejected, TRUE);
            $req =  print_r($this->post() + $this->get(), TRUE);
            self::logMsg("Defend rejected: $reject\nresidual: $req");
        }
    }


    private function examine($request){
        $req = $this->clip($request);
        $result = filter_var_array($req, self::FILTER_ARGS, FALSE);
        if ($this->size($result) !== $this->size($req)){
            $this->rejected = $this->rejected + $this->rejects($req, $result);
        }
        return $result;
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


    /********************************************************************************
    Returns $val truncated to a maximum length specified in the global $clip array

    $key    String    the $key of the global $clip array specifying the truncated length
    $val    String    A string to be truncated according to global $clip array
    ********************************************************************************/
    function clip($data, $key=NULL){
        $clipped = $data;
        if (is_array($data)){
            if(is_null($key)){
                foreach($data as $key => $val){
                    $clipped[$key] = $this->clip($val, $key);
                }
            } elseif(array_key_exists($key, $data)) {
                $clipped[$key] = $this->clip($data[$key], $key);
            }
        } elseif(array_key_exists($key, self::CLIP)){
            $clipped = substr($data, 0, self::CLIP[$key]);
            if ($clipped !== $data){
                Qwik::logMsg("Defend clipped [$key] $clipped");
            }
        }
        return $clipped;
    }
}

?>
