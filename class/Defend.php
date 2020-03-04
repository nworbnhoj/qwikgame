<?php 

require_once 'Qwik.php';
require_once 'Filter.php';


class Defend extends Qwik {

    // An array of maximum string lengths. Used by: clip()
    const CLIP = array(
        'admin1'      => 100,
        'address'     => 200,
        'description' => 200,
        'filename'    => 50,
        'input'       => 200,
        'html'        => 2000,
        'key'         => 30,
        'locality'    => 100,
        'message'     => 200,
        'msg'         => 200,
        'lang'        => 2,
        'name'        => 100,
        'nick'        => 20,
        'note'        => 2000,
        'phrase'      => 2000,
        'region'      => 50,
        'reply'       => 3,
        'route'       => 100,
        'tz'          => 100,
        'venue'       => 150
    );


    
    const FILTER_ARGS = array(
        'admin'    => FILTER_DEFAULT,
        'admin1'   => FILTER_DEFAULT,
        'address'  => FILTER_DEFAULT,
        'ability'  => Filter::ABILITY,
        'account'  => FILTER_DEFAULT,
        'beckon'   => FILTER_DEFAULT,
        'country'  => Filter::COUNTRY,
        'email'    => FILTER_VALIDATE_EMAIL,
        'Fri'      => Filter::HOURS,
        'filename' => FILTER_DEFAULT,
        'game'     => Filter::GAME,
        'hour'     => Filter::HOURS,
        'html'     => FILTER_DEFAULT,
        'input'    => FILTER_DEFAULT,
        'id'       => Filter::ID,
        'key'      => FILTER_DEFAULT,
        'invite'   => Filter::INVITE,
        'lang'     => Filter::LANG,
        'lat'      => Filter::LAT,
        'lng'      => Filter::LNG,
        'locality' => FILTER_DEFAULT,
        'message'  => FILTER_DEFAULT,
        'Mon'      => Filter::HOURS,
        'msg'      => FILTER_DEFAULT,
        'name'     => FILTER_DEFAULT,
        'note'     => FILTER_DEFAULT,
        'nick'     => FILTER_DEFAULT,
        'parity'   => Filter::PARITY,
        'phone'    => Filter::PHONE,
        'phrase'   => FILTER_DEFAULT,
        'pid'      => Filter::PID,
        'placeid'  => FILTER_DEFAULT,
        'qid'      => Filter::QID,
        'qwik'     => Filter::QWIK,
        'reply'    => FILTER_DEFAULT,
        'reply-email' => FILTER_VALIDATE_EMAIL,
        'route'    => FILTER_DEFAULT,
        'Sat'      => Filter::HOURS,
        'skip'     => FILTER_DEFAULT,
        'smtwtfs'  => Filter::HOURS,
        'str-num'  => Filter::STR_NUM,
        'Sun'      => Filter::HOURS,
        'Thu'      => Filter::HOURS,
        'time'     => FILTER_DEFAULT,
        'today'    => Filter::HOURS,
        'token'    => Filter::TOKEN,
        'tomorrow' => Filter::HOURS,
        'Tue'      => Filter::HOURS,
        'tz'       => FILTER_DEFAULT,
        'region'   => FILTER_DEFAULT,
        'rep'      => Filter::REP,
        'repost'   => Filter::REPOST,
        'rival'    => FILTER_VALIDATE_EMAIL,
        'title'    => FILTER_DEFAULT,
    //        'url'        => FILTER_VALIDATE_URL,
        'url'      => FILTER_DEFAULT,
        'venue'    => FILTER_DEFAULT,
        'vid'      => FILTER_DEFAULT,
        'Wed'      => Filter::HOURS
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


    # SECURITY escape all parameters to prevent malicious code insertion
    # http://au.php.net/manual/en/function.htmlentities.php
    static private function reclaw($data){
        if (is_array($data)){
            foreach($data as $key => $val){
                $data[$key] = self::reclaw($val);
            }
        } else {
            $data = html_entity_decode($data, ENT_QUOTES | ENT_HTML5);
        }
        return $data;
    }



    private $get;
    private $post;
    private $rejected = array();


    public function __construct(){
        parent::__construct();
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
            $msg = print_r($rejected, TRUE);
            self::logMsg("Defend rejected: $msg");
        }
    }


    private function examine($request){
        $req = self::declaw($request);
        $req = $this->clip($req);
        $result = filter_var_array($req, self::FILTER_ARGS, FALSE);
        if ($this->size($result) !== $this->size($req)){
        	$this->rejected = $this->rejected + $this->rejects($req, $result);
        }
        return $result;
    }



    private function rejects($raw, $processed){
        $rejects = NULL;
        if(is_array($raw)){
            $rejects = array();
            foreach($raw as $key => $value){
                $missing = NULL;
                if (array_key_exists($key, $processed)){
                    $missing = $this->rejects($value, $processed[$key]);
                } else {
                    $missing = print_r($value, TRUE);
                }
                if(!empty($missing)){
                    $rejects[$key] = $missing;
                }
            }
        } elseif($raw != $processed) {    // weak test as some filters convert type
            $rejects = print_r($raw, TRUE);
        }
        return empty($rejects) ? NULL : $rejects;
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
    Return the $data string with all but a small set of safe characters removed

    $data    String    An arbitrary string

    Safe character set:
        abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789|:@ _-,./#

    ********************************************************************************/
    private function scrub($data){
        if (is_array($data)){
            foreach($data as $key => $val){
                $data[$key] = $this->clip($this->scrub($val), $key);
            }
        } else {
            $data = preg_replace("/[^(a-zA-Z0-9|:@ \_\-\,\.\/\#]*/", '', $data);
        }
        return $data;
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
                Page::logMsg("Defend clipped [$key] $clipped");
            }
        }
        return $clipped;
    }
}

?>
