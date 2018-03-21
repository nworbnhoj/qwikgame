<?php


require_once 'Qwik.php';

class Filter extends Qwik {

    const VALID_PARITY = array('any','similar','matching', '-2', '-1', '0', '1', '2');
    const VALID_QWIK = array('accept', 'account', 'activate', 'available', 'cancel', 'deactivate', 'decline', 'delete', 'familiar', 'feedback', 'keen', 'login', 'logout', 'msg', 'recover', 'region', 'upload');


    const OPT_ABILITY = array('min_range' => 0, 'max_range' => 4);
    const OPT_PARITY  = array('min_range' => -2, 'max_range' => 2);
    const OPT_REP     = array('min_range' => -1, 'max_range' => 1);
    const OPT_HRS     = array('min_range' => 0, 'max_range' => 16777215);
    const OPT_LAT     = array('min_range' => -90, 'max_range' => 90);
    const OPT_LNG     = array('min_range' => -180, 'max_range' => 180);


    const ABILITY = array('filter'=>FILTER_VALIDATE_INT,   'options'=>Filter::OPT_ABILITY);
    const COUNTRY = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::country');
    const HOURS   = array('filter'=>FILTER_VALIDATE_INT,   'options'=>Filter::OPT_HRS);
    const GAME    = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::game');
    const ID      = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::ID');
    const INVITE  = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::invite');
    const LAT     = array('filter'=>FILTER_VALIDATE_FLOAT, 'options'=>Filter::OPT_LAT);
    const LNG     = array('filter'=>FILTER_VALIDATE_FLOAT, 'options'=>Filter::OPT_LNG);
    const PARITY  = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::parity');
    const PHONE   = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::phone');
    const PID     = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::PID');
    const QWIK    = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::qwik');
    const REP     = array('filter'=>FILTER_VALIDATE_INT,   'options'=>Filter::OPT_REP);
    const REPOST  = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::repost');
    const TOKEN   = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::token');


    static function game($val){
        return array_key_exists($val, self::qwikGames()) ? $val : FALSE;
    }


    static function country($val){
        return array_key_exists($val, self::countries()) ? $val : FALSE;
    }


    static function ID($val){
        return strlen($val) == 6 ? $val : FALSE;
    }


    static function invite($val){
        return filter_var($val, FILTER_VALIDATE_EMAIL);
    }


    static function parity($val){
        return in_array($val, self::VALID_PARITY) ? $val : FALSE;
    }


    static function PID($val){
        return strlen($val) == 64 ? $val : FALSE;
    }


    static function phone($val){
        return strlen($val) <= 20 ? $val : FALSE;
    }


    static function repost($val){
        return $val;
    }


    static function qwik($val){
        return in_array($val, self::VALID_QWIK) ? $val : FALSE;
    }


    static function token($val){
        return strlen($val) == 10 ? $val : FALSE;
    }

}


?>

