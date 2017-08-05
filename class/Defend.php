<?php 

class Defend {

    private $get;
    private $post;
    

	public function __construct(){}
	
	
    
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
	
	
	
	private function examine($request){
        $req = $this->declaw($request);

        $ability_opt = array('min_range' => 0, 'max_range' => 4);
        $parity_opt  = array('min_range' => -2, 'max_range' => 2);
        $rep_opt     = array('min_range' => -1, 'max_range' => 1);
        $hrs_opt     = array('min_range' => 0, 'max_range' => 16777215);
        
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
            'smtwtfs'  => array('filter'=>FILTER_VALIDATE_INT, 'options'=>$hrs_opt),
            'address'  => FILTER_DEFAULT,
            'ability'  => array('filter'=>FILTER_VALIDATE_INT, 'options'=>$ability_opt),
            'account'  => FILTER_DEFAULT,
            'country'  => array('filter'=>FILTER_CALLBACK,     'options'=>$fvCountry),
            'email'    => FILTER_VALIDATE_EMAIL,
            'Fri'      => array('filter'=>FILTER_VALIDATE_INT, 'options'=>$hrs_opt),
            'filename' => FILTER_DEFAULT,
            'game'     => array('filter'=>FILTER_CALLBACK,     'options'=>$fvGame),
            'id'       => array('filter'=>FILTER_CALLBACK,     'options'=>$fvID),
            'invite'   => array('filter'=>FILTER_CALLBACK,     'options'=>$fvInvite),
            'Mon'      => array('filter'=>FILTER_VALIDATE_INT, 'options'=>$hrs_opt),
            'msg'      => FILTER_DEFAULT,
            'name'     => FILTER_DEFAULT,
            'nickname' => FILTER_DEFAULT,
            'parity'   => array('filter'=>FILTER_CALLBACK,     'options'=>$fvParity),
            'phone'    => array('filter'=>FILTER_CALLBACK,     'options'=>$fvPhone),
            'pid'      => array('filter'=>FILTER_CALLBACK,     'options'=>$fvPID),
            'qwik'     => array('filter'=>FILTER_CALLBACK,     'options'=>$fvQwik),
            'Sat'      => array('filter'=>FILTER_VALIDATE_INT, 'options'=>$hrs_opt),
            'state'    => FILTER_DEFAULT,
            'suburb'   => FILTER_DEFAULT,
            'Sun'      => array('filter'=>FILTER_VALIDATE_INT, 'options'=>$hrs_opt),
            'Thu'      => array('filter'=>FILTER_VALIDATE_INT, 'options'=>$hrs_opt),
            'time'     => FILTER_DEFAULT,
            'today'    => array('filter'=>FILTER_VALIDATE_INT, 'options'=>$hrs_opt),
            'token'    => array('filter'=>FILTER_CALLBACK,     'options'=>$fvToken),
            'tomorrow' => array('filter'=>FILTER_VALIDATE_INT, 'options'=>$hrs_opt),
            'Tue'      => array('filter'=>FILTER_VALIDATE_INT, 'options'=>$hrs_opt),
            'tz'       => FILTER_DEFAULT,
            'region'   => FILTER_DEFAULT,
            'rep'      => array('filter'=>FILTER_VALIDATE_INT, 'options'=>$rep_opt),
            'repost'   => array('filter'=>FILTER_CALLBACK,     'options'=>$fvRepost),
            'rival'    => FILTER_VALIDATE_EMAIL,
            'title'    => FILTER_DEFAULT,
    //        'url'        => FILTER_VALIDATE_URL,
            'url'      => FILTER_DEFAULT,
            'venue'    => FILTER_DEFAULT,
            'Wed'      => array('filter'=>FILTER_VALIDATE_INT, 'options'=>$hrs_opt)
        );

        $result = filter_var_array($req, $args);

        if(in_array(FALSE, $result, TRUE)){
            $this->req = array();
            Page::logMsg("The Defense Filter rejected the input request.");
        }
        
        return $req;
    //    return declaw($req);
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
                $data[$key] = $this->clip($key, $this->scrub($val));
            }
        } else {
            $data = preg_replace("/[^(a-zA-Z0-9|:@ \_\-\,\.\/\#]*/", '', $data);
        }
        return $data;
    }
    
    
    

    // An array of maximum string lengths.
    // Used by: clip()
    private $clip = array(
        'address'     => 200,
        'description' => 200,
        'filename'    => 50,
        'nickname'    => 20,
        'note'        => 2000,
        'region'      => 50,
        'state'       => 50,
        'suburb'      => 50,
        'tz'          => 100,
        'venue'       => 150
    );

    /********************************************************************************
    Returns $val truncated to a maximum length specified in the global $clip array

    $key    String    the $key of the global $clip array specifying the truncated length
    $val    String    A string to be truncated according to global $clip array
    ********************************************************************************/
    function clip($key, $val){
        return array_key_exists($key, $this->clip) ? substr($val, 0, $this->clip[$key]) : $val ;
    }


/************* FILTERS ******************/


    private function fvGame($val){
        global $games;
        return array_key_exists($val, $games) ? $val : FALSE;
    }


    private function fvCountry($val){
        global $countries;
        return array_key_exists($val, $countries) ? $val : FALSE;
    }


    private function fvID($val){
        return strlen($val) == 6 ? $val : FALSE;
    }


    private function fvInvite($val){
        if (is_array($val)){
            return true;    // *********** more validation required **************
        }
        return false;
    }


    private function fvParity($val){
        global $parityFilter;
        return in_array($val, $parityFilter) ? $val : FALSE;
    }


    private function fvPID($val){
        return strlen($val) == 64 ? $val : FALSE;
    }


    private function fvPhone($val){
        return strlen($val) <= 10 ? $val : FALSE;
    }


    private function fvRepost($val){
        return $val;
    }


    private function fvQwik($val){
        global $qwiks;
        return in_array($val, $qwiks) ? $val : FALSE;
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
