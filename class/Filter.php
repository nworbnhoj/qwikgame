<?php


require_once 'Qwik.php';

class Filter extends Qwik {

    const VALID_ADMIN = array('acceptTranslation'=>'', 'rejectTranslation'=>'');
    const VALID_CHECKBOX = array('on'=>'');
    const VALID_LANG = array('ar'=>'', 'bg'=>'', 'en'=>'', 'es'=>'', 'fr'=>'', 'hi'=>'', 'jp'=>'', 'ru'=>'', 'zh'=>'');
    const VALID_PARITY = array('any'=>'', 'similar'=>'', 'matching'=>'', '-2'=>'', '-1'=>'', '0'=>'', '1'=>'', '2'=>'');
    const VALID_QWIK = array('accept'=>'', 'account'=>'', 'activate'=>'', 'available'=>'', 'cancel'=>'', 'deactivate'=>'', 'decline'=>'', 'delete'=>'', 'friend'=>'', 'keen'=>'', 'login'=>'', 'logout'=>'', 'msg'=>'', 'recover'=>'', 'region'=>'', 'register'=>'', 'translate'=>'', 'upload'=>'');


    const OPT_PARITY  = array('min_range' => -2, 'max_range' => 2);
    const OPT_REP     = array('min_range' => -1, 'max_range' => 1);
    const OPT_HRS     = array('min_range' => 0, 'max_range' => 16777215);
    const OPT_LAT     = array('min_range' => -90, 'max_range' => 90);
    const OPT_LNG     = array('min_range' => -180, 'max_range' => 180);
    const OPT_STR_NUM = array('min_range' => 0, 'max_range' => 10000);


    const ADMIN   = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::admin');
    const CHECKBOX= array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::checkbox');
    const COUNTRY = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::country');
    const HONEYPOT= array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::honeypot');
    const HOURS   = array('filter'=>FILTER_VALIDATE_INT,   'options'=>Filter::OPT_HRS);
    const GAME    = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::game');
    const ID      = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::ID');
    const INVITE  = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::invite');
    const LANG    = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::lang');
    const LAT     = array('filter'=>FILTER_VALIDATE_FLOAT, 'options'=>Filter::OPT_LAT);
    const LNG     = array('filter'=>FILTER_VALIDATE_FLOAT, 'options'=>Filter::OPT_LNG);
    const PARITY  = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::parity');
    const PHONE   = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::phone');
    const PID     = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::PID');
    const PLACEID = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::placeID');
    const PUSHTOK = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::pushToken');
    const PUSHKEY = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::pushKey');
    const QWIK    = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::qwik');
    const REP     = array('filter'=>FILTER_VALIDATE_INT,   'options'=>Filter::OPT_REP);
    const STR_NUM = array('filter'=>FILTER_VALIDATE_INT,   'options'=>Filter::OPT_STR_NUM);
    const TOKEN   = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::token');


    static function checkbox($val){
        return isset(self::VALID_CHECKBOX[$val]) ? $val : FALSE;
    }


    static function admin($val){
        return isset(self::VALID_ADMIN[$val]) ? $val : FALSE;
    }


    static function game($val){
        return isset(self::qwikGames()[$val]) ? $val : FALSE;
    }


    static function country($val){
        return isset(self::countries()[$val]) ? $val : FALSE;
    }


    static function honeypot($val){
        return FALSE;
    }


    static function ID($val){
        return self::preg($val, "[a-zA-Z0-9]", 6, 6);
    }


    static function invite($val){
        return filter_var($val, FILTER_VALIDATE_EMAIL);
    }


    static function lang($val){
        return isset(self::VALID_LANG[$val]) ? $val : FALSE;
    }


    static function parity($val){
        return isset(self::VALID_PARITY[$val]) ? $val : FALSE;
    }


    static function PID($val){
        return self::preg($val, "[a-z0-9]", 64, 64);
    }


    static function phone($val){
        return strlen($val) <= 20 ? $val : FALSE;
    }


    static function placeID($val){
        return self::preg($val, "[a-zA-Z0-9_-]", 500);
    }


    static function pushKey($val){
        return self::preg($val, "[a-zA-Z0-9_-+/]", 1024);
    }


    static function pushToken($val){
        return self::preg($val, "[a-zA-Z0-9_-+]==", 128);
    }


    static function qwik($val){
        return isset(self::VALID_QWIK[$val]) ? $val : FALSE;
    }


    static function token($val){
        return self::preg($val, "[a-zA-Z0-9]", 10, 10);
    }


    static function preg($val, $pat, $max=2048, $min=0){
        $len = strlen($val);
        if ($len < $min
        || $len > $max
        || !preg_match("#^$pat+$#", $val)) {
          return FALSE;
        }
        return $val;
    }

}


?>


