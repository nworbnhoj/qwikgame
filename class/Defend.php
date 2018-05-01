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
        'locality'    => 100,
        'msg'         => 200,
        'name'        => 100,
        'nickname'    => 20,
        'note'        => 2000,
        'region'      => 50,
        'route'       => 100,
        'tz'          => 100,
        'venue'       => 150
    );


    private $get;
    private $post;
    private $rejected;


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
        if (is_null($this->rejected)){
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
        $req = $this->declaw($request);
        $req = $this->clip($req);

        $args = array(
            'admin1'   => FILTER_DEFAULT,
            'address'  => FILTER_DEFAULT,
            'ability'  => Filter::ABILITY,
            'account'  => FILTER_DEFAULT,
            'country'  => Filter::COUNTRY,
            'email'    => FILTER_VALIDATE_EMAIL,
            'Fri'      => Filter::HOURS,
            'filename' => FILTER_DEFAULT,
            'game'     => Filter::GAME,
            'hour'     => Filter::HOURS,
            'id'       => Filter::ID,
            'invite'   => Filter::INVITE,
            'lat'      => Filter::LAT,
            'lng'      => Filter::LNG,
            'locality' => FILTER_DEFAULT,
            'Mon'      => Filter::HOURS,
            'msg'      => FILTER_DEFAULT,
            'name'     => FILTER_DEFAULT,
            'note'     => FILTER_DEFAULT,
            'nickname' => FILTER_DEFAULT,
            'parity'   => Filter::PARITY,
            'phone'    => Filter::PHONE,
            'pid'      => Filter::PID,
            'placeid'  => FILTER_DEFAULT,
            'qwik'     => Filter::QWIK,
            'route'    => FILTER_DEFAULT,
            'Sat'      => Filter::HOURS,
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

        $result = filter_var_array($req, $args, FALSE);

        $changed = $this->size($result) !== $this->size($req);
        $rejects = $changed ? $this->rejects($req, $result) : array() ;
        if(is_null($this->rejected)){
            $this->rejected = $rejects;
        } else {
            $this->rejected += $rejects;
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


/************* old code ******************/



    # SECURITY escape all parameters to prevent malicious code insertion
    # http://au.php.net/manual/en/function.htmlentities.php
    private function declaw($cat){
        if (is_array($cat)){
            $kitten = array();
            foreach($cat as $key => $val){
                $kitten[$this->declaw($key)] = $this->declaw($val);
            }
        } else {
            $kitten = htmlspecialchars(trim($cat), ENT_QUOTES | ENT_HTML5, "UTF-8");
        }
        return $kitten;
    }


    # SECURITY escape all parameters to prevent malicious code insertion
    # http://au.php.net/manual/en/function.htmlentities.php
    private function reclaw($data){
        if (is_array($data)){
            foreach($data as $key => $val){
                $data[$key] = reclaw($val);
            }
        } else {
            $data = html_entity_decode($data, ENT_QUOTES | ENT_HTML5);
        }
        return $data;
    }

}

?>
