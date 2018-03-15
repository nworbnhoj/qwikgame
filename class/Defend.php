<?php 

require_once 'Qwik.php';

class Defend extends Qwik {

    // An array of maximum string lengths. Used by: clip()
    const CLIP = array(
        'address'     => 200,
        'description' => 200,
        'filename'    => 50,
        'msg'         => 200,
        'nickname'    => 20,
        'note'        => 2000,
        'region'      => 50,
        'tz'          => 100,
        'venue'       => 150
    );

    const VALID_QWIK = array('accept', 'account', 'activate', 'available', 'cancel', 'deactivate', 'decline', 'delete', 'familiar', 'feedback', 'keen', 'login', 'logout', 'msg', 'recover', 'region', 'upload');

    const VALID_PARITY = array('any','similar','matching', '-2', '-1', '0', '1', '2');


    private $get;
    private $post;
    private $rejected;


    public function __construct(){
        parent::__construct();
    }


    public function get(){
        if (is_null($this->get)){
            $get = $_GET;
            $this->get = empty($get) ? array() : $this->examine($_GET);
        }
        return $this->get;
    }


    public function post(){
        if (is_null($this->post)){
            $post = $_POST;
            $this->post = empty($post) ? array() : $this->examine($_POST);
        }
        return $this->post;
    }


    public function request(){
       return $this->post() + $this->get();
    }


    public function rejected(){
        if (is_null($this->rejected)){
            $this->request();
        }
        return $this->rejected;
    }


    public function logRejected(){
        $rejected = print_r($this->rejected(), TRUE);
        Page::logMsg("Defend rejected: $rejected");
    }




    private function examine($request){
        $req = $this->declaw($request);
        $req = $this->clip($req);

        $ability_opt = array('min_range' => 0, 'max_range' => 4);
        $parity_opt  = array('min_range' => -2, 'max_range' => 2);
        $rep_opt     = array('min_range' => -1, 'max_range' => 1);
        $hrs_opt     = array('min_range' => 0, 'max_range' => 16777215);
        $lat_opt     = array('min_range' => -90, 'max_range' => 90);
        $lng_opt     = array('min_range' => -180, 'max_range' => 180);

        $fvCountry = array($this,'fvCountry');
        $fvGame    = array($this,'fvGame');
        $fvID      = array($this,'fvID');
        $fvInvite  = array($this,'fvInvite');
        $fvParity  = array($this,'fvParity');
        $fvPhone   = array($this,'fvPhone');
        $fvPID     = array($this,'fvPID');
        $fvQwik    = array($this,'fvQwik');
        $fvToken   = array($this,'fvToken');
        $fvRepost  = array($this,'fvRepost');

        $args = array(
            'smtwtfs'  => array('filter'=>FILTER_VALIDATE_INT,   'options'=>$hrs_opt),
            'address'  => FILTER_DEFAULT,
            'ability'  => array('filter'=>FILTER_VALIDATE_INT,   'options'=>$ability_opt),
            'account'  => FILTER_DEFAULT,
            'country'  => array('filter'=>FILTER_CALLBACK,       'options'=>$fvCountry),
            'email'    => FILTER_VALIDATE_EMAIL,
            'Fri'      => array('filter'=>FILTER_VALIDATE_INT,   'options'=>$hrs_opt),
            'filename' => FILTER_DEFAULT,
            'game'     => array('filter'=>FILTER_CALLBACK,       'options'=>$fvGame),
            'id'       => array('filter'=>FILTER_CALLBACK,       'options'=>$fvID),
            'invite'   => array('filter'=>FILTER_CALLBACK,       'options'=>$fvInvite),
            'lat'      => array('filter'=>FILTER_VALIDATE_FLOAT, 'options'=>$lat_opt),
            'lng'      => array('filter'=>FILTER_VALIDATE_FLOAT, 'options'=>$lng_opt),
            'Mon'      => array('filter'=>FILTER_VALIDATE_INT,   'options'=>$hrs_opt),
            'msg'      => FILTER_DEFAULT,
            'name'     => FILTER_DEFAULT,
            'note'     => FILTER_DEFAULT,
            'nickname' => FILTER_DEFAULT,
            'parity'   => array('filter'=>FILTER_CALLBACK,       'options'=>$fvParity),
            'phone'    => array('filter'=>FILTER_CALLBACK,       'options'=>$fvPhone),
            'pid'      => array('filter'=>FILTER_CALLBACK,       'options'=>$fvPID),
            'qwik'     => array('filter'=>FILTER_CALLBACK,       'options'=>$fvQwik),
            'Sat'      => array('filter'=>FILTER_VALIDATE_INT,   'options'=>$hrs_opt),
            'Sun'      => array('filter'=>FILTER_VALIDATE_INT,   'options'=>$hrs_opt),
            'Thu'      => array('filter'=>FILTER_VALIDATE_INT,   'options'=>$hrs_opt),
            'time'     => FILTER_DEFAULT,
            'today'    => array('filter'=>FILTER_VALIDATE_INT,   'options'=>$hrs_opt),
            'token'    => array('filter'=>FILTER_CALLBACK,       'options'=>$fvToken),
            'tomorrow' => array('filter'=>FILTER_VALIDATE_INT,   'options'=>$hrs_opt),
            'Tue'      => array('filter'=>FILTER_VALIDATE_INT,   'options'=>$hrs_opt),
            'tz'       => FILTER_DEFAULT,
            'region'   => FILTER_DEFAULT,
            'rep'      => array('filter'=>FILTER_VALIDATE_INT,   'options'=>$rep_opt),
            'repost'   => array('filter'=>FILTER_CALLBACK,       'options'=>$fvRepost),
            'rival'    => FILTER_VALIDATE_EMAIL,
            'title'    => FILTER_DEFAULT,
    //        'url'        => FILTER_VALIDATE_URL,
            'url'      => FILTER_DEFAULT,
            'venue'    => FILTER_DEFAULT,
            'vid'      => FILTER_DEFAULT,
            'Wed'      => array('filter'=>FILTER_VALIDATE_INT,   'options'=>$hrs_opt)
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
            foreach($raw as $key => $value){
                $missing = NULL;
                if (array_key_exists($key, $processed)){
                    $missing = $this->rejects($value, $processed[$key]);
                } else {
                    $missing = print_r($value, TRUE);
                }
                if(!is_null($missing)){
                    $rejects[$key] = $missing;
                }
            }
        } elseif($raw != $processed) {    // weak test as some filters convert type
            $rejects = print_r($raw, TRUE);
        }
        return $rejects;
    }


    /*********************************************************
    *
    * returns the number of non-empty data (ie keys+value)
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


/************* FILTERS ******************/


    private function fvGame($val){
        return array_key_exists($val, self::qwikGames()) ? $val : FALSE;
    }


    private function fvCountry($val){
        return array_key_exists($val, self::countries()) ? $val : FALSE;
    }


    private function fvID($val){
        return strlen($val) == 6 ? $val : FALSE;
    }


    private function fvInvite($val){
        return filter_var($val, FILTER_VALIDATE_EMAIL);
    }


    private function fvParity($val){
        return in_array($val, Defend::VALID_PARITY) ? $val : FALSE;
    }


    private function fvPID($val){
        return strlen($val) == 64 ? $val : FALSE;
    }


    private function fvPhone($val){
        return strlen($val) <= 20 ? $val : FALSE;
    }


    private function fvRepost($val){
        return $val;
    }


    private function fvQwik($val){
        return in_array($val, Defend::VALID_QWIK) ? $val : FALSE;
    }


    private function fvToken($val){
        return strlen($val) == 10 ? $val : FALSE;
    }



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
