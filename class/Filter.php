<?php


require_once 'Qwik.php';

class Filter extends Qwik {

    const HSC_FLAGS = ENT_QUOTES | ENT_HTML5;

    const VALID_ADMIN = array(
          'acceptTranslation'=>'',
          'rejectTranslation'=>''
    );
    const VALID_CHECKBOX = array(
          'on'=>''
    );
    const VALID_LANG = array(
          'ar'=>'',
          'bg'=>'',
          'en'=>'',
          'es'=>'',
          'fr'=>'',
          'hi'=>'',
          'jp'=>'',
          'ru'=>'',
          'zh'=>''
    );
    const VALID_PARITY = array(
         'any'=>'',
          'similar'=>'',
          'matching'=>'',
          '-2'=>'',
          '-1'=>'',
          '0'=>'',
          '1'=>'',
          '2'=>''
    );
    const VALID_QWIK = array(
          'accept'=>'',
          'account'=>'',
          'activate'=>'',
          'available'=>'',
          'cancel'=>'',
          'deactivate'=>'',
          'decline'=>'',
          'delete'=>'',
          'feedback'=>'',
          'friend'=>'',
          'keen'=>'',
          'login'=>'',
          'logout'=>'',
          'msg'=>'',
          'recover'=>'',
          'region'=>'',
          'register'=>'',
          'translate'=>'',
          'upload'=>'',
          'quit'=>''
    );
    
    
  
  
  // white-list of sha256 hashes of html snippets submitted by json for replication
  // Update when <div class='json'> changes OR included translations change
  const HTML_SHA256 = array(
   '12193d631dcd6062aa5ed612d227b41f111fa47ff9b736d3a0d362facddac1a0' => 'en cancelled.match.listing',
   '7b7bbf074df104c3d9dbb4226277805844663609adabd81d281ddb923a909f1d' => 'en feedback.match.listing',
   'cc01603abdb6a1d3e87992dcb7918584751177f10b3288c1ce22f04ce50dedc5' => 'en confirmed.match.listing',
   '45593caf851800cbdd5ae0f403b7196c4038fecbd0822209aef4050425d2381a' => 'en accepted.match.listing',
   '2339bf25adb414b5e1bc59362da490731f21f686d05d3d57b99a0569caf2aa5a' => 'en invitation.match.listing',
   '397dc67250c2cedc7d527a8c5b999103363bc2466566263ec36387c33d9567fc' => 'en keen.match.listing',
   'dfbfe806cf21f0697fbea049e2e24a56b026e3dfb5eb92852e84c1af41be19b7' => 'en history.match.listing',
   '5642145bedf0b02e24b9b14a5b6134076f8c93ef549a9ae455b36fb6263c96c1' => 'en favorite.listing',
   '041594c6847191044f6c2a9e755233761993152788a5df5b0221cd4247f3efa8' => 'en friend.listing',
   '7efff865bcdec963e62978bb95e757ec7301187113ec8f4d1d1ba2f6e86387c5' => 'en upload.listing',
   
   'dfec5572448c9ce067977d074f116848d03e41a931fbf232d53d081185c8d6a8' => 'bg cancelled.match.listing',
   '53091917efc7f63b3a5a9a7813a2b32dd333a34f5f0c619b07d537add1f3800d' => 'bg feedback.match.listing',
   'ded633890cccfb6367c92c73756d8e7fc3c8e50c0a2012505ae92642fdfe6c53' => 'bg confirmed.match.listing',
   'a0f3ac729a2b966d39cb2be6afdd1d4c5befdae9cf0ef5723c5a5bdd7380a119' => 'bg accepted.match.listing',
   '9a72e18e6d620a1156a20a73de6e29b0009c96e76d6b791fc29cab51ae9f610c' => 'bg invitation.match.listing',
   'a3ee488974e0da90b1a15a8e5a514ad654aaf4c127a0e98d109f5e8d11979e4a' => 'bg keen.match.listing',
   'c191df15129bc0597c71c185f0315136b692e49bce74a13641c4dc663408ec12' => 'bg history.match.listing',
   '5642145bedf0b02e24b9b14a5b6134076f8c93ef549a9ae455b36fb6263c96c1' => 'bg favorite.listing',
   '34c83baa98909de55fee6cd76bd35b44d56c5235f6388002f9d129db259a0498' => 'bg friend.listing',
   '40ee332a534d19cabf1349a8c8c61896dd6366cd82536e6d7927446e1aea8520' => 'bg upload.listing',
   
   '149d83ab8de7ba10946c7d37f0649cdc12497f6c6482f97409e5b35a6d64fba6' => 'es cancelled.match.listing',
   '0798aced7ff176db6c81090f09ed205312293a448c5a17b9bc4a8f0c8c77e66c' => 'es feedback.match.listing',
   '4aabe0b4dad5c45265ceffd110e6250ce22665ad8832851656a10382d3509344' => 'es confirmed.match.listing',
   '7f6a9e6dd4b2351a3b230a95b90edc180b500222971b190e1a85bc29935c51d6' => 'es accepted.match.listing',
   '8a444d8be0ae7bd048a4ee497351dee93842fe40b7475d263291c82cac1fdc1c' => 'es invitation.match.listing',
   '2002c9b11068893f008ae7f7734769b94a04cb57ace5d96b58322a293c506598' => 'es keen.match.listing',
   'ba57326428d6b77086efdb0a875679139e51896dae4a416c429946d4c826d087' => 'es history.match.listing',
   'af953e4d2071550dd1b29fb903588b58dec96b2463d0cb44b1785783264c3057' => 'es favorite.listing',
   '8294a5fd8e4d2076de711ed9d6c3fa4cbc00454fc4214e5556e233b69cfff629' => 'es friend.listing',
   '9ffc6d3819f2840e5c436b794a262d60cb8ca50f6399fbf111ea8d91e25fa958' => 'es upload.listing',
   
   '223426f729680df4da1b6abea91b3a2cedc5baa902c3e4416124bdcfabb505f4' => 'zh cancelled.match.listing',
   'f89094d8f9ba84829bd034edecd53664628ec0b71084d60649ed4f843eec42fb' => 'zh feedback.match.listing',
   'cc01603abdb6a1d3e87992dcb7918584751177f10b3288c1ce22f04ce50dedc5' => 'zh confirmed.match.listing',
   '54f343cbff85a33b5772090cb53404f7c50f3081a5a530dd9c76a52fb96ab1cb' => 'zh accepted.match.listing',
   'ba1bc0abab68ee9eecffc41f8118b0a84c82cc197a6e1efc4d6a6f76af2e4878' => 'zh invitation.match.listing',
   'db7cffb3f6b3eaf63a4be8525fcefe2adb4aacd8a01e608b0c8c172c62bbb0ea' => 'zh keen.match.listing',
   'ba57326428d6b77086efdb0a875679139e51896dae4a416c429946d4c826d087' => 'zh history.match.listing',
   'd080f072fe751b57210a1d136b8d010c87f321248131a28b4f114a9e7778a2ed' => 'zh favorite.listing',
   'acdbe4b2e7762fe40039ad24743ea79ee439e2a8f84f2bae90fda1d05cae02c7' => 'zh friend.listing',
   '707cbeb51fabf3207c08aba7afb79c46d7d77050bd5c784b2a20ad1684c6d670' => 'zh upload.listing'
  );


    const OPT_PARITY  = array('min_range' => -2, 'max_range' => 2);
    const OPT_REP     = array('min_range' => -1, 'max_range' => 1);
    const OPT_HRS     = array('min_range' => 0, 'max_range' => 16777215);
    const OPT_LAT     = array('min_range' => -90, 'max_range' => 90);
    const OPT_LNG     = array('min_range' => -180, 'max_range' => 180);


    const ADMIN   = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::admin');
    const AVOID   = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::avoid');
    const CHECKBOX= array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::checkbox');
    const COUNTRY = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::country');
    const EMAIL   = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::email');
    const HONEYPOT= array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::honeypot');
    const HOURS   = array('filter'=>FILTER_VALIDATE_INT,   'options'=>Filter::OPT_HRS);
    const HTML    = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::html');
    const GAME    = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::game');
    const ID      = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::ID');
    const INVITE  = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::invite');
    const LANG    = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::lang');
    const LAT     = array('filter'=>FILTER_VALIDATE_FLOAT, 'options'=>Filter::OPT_LAT);
    const LNG     = array('filter'=>FILTER_VALIDATE_FLOAT, 'options'=>Filter::OPT_LNG);
    const MSG     = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::msg');
    const NAME    = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::name');
    const PARITY  = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::parity');
    const PHONE   = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::phone');
    const PID     = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::PID');
    const PLACEID = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::placeID');
    const PUSHTOK = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::pushToken');
    const PUSHKEY = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::pushKey');
    const QWIK    = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::qwik');
    const REGION  = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::region');
    const REP     = array('filter'=>FILTER_VALIDATE_INT,   'options'=>Filter::OPT_REP);
    const VENUENAME= array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::venuename');
    const VID     = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::vid');
    const TOKEN   = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::token');


    static function checkbox($val){
        return isset(self::VALID_CHECKBOX[$val]) ? $val : FALSE;
    }


    static function admin($val){
        return isset(self::VALID_ADMIN[$val]) ? $val : FALSE;
    }
    	
    
    static function avoid($val){
      if (self::strlen($val, 2000)
      && mb_ereg_match("(([\w\- _&,.]+[|]){0,3}[A-Z]{2}:?)+$", $val)){
        return $val;
      }
      return FALSE;
    }
    	
    
    static function email($val){
      if (self::strlen($val, 2000)
      && mb_ereg_match("(([\w\- _&,.]+[|]){0,3}[A-Z]{2}:?)+$", $val)){
        return $val;
      }
      return FALSE;
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
      if (self::strlen($val, 6, 6)
      && preg_match("#^[a-zA-Z0-9]+$#", $val)){
        return $val;
      }
      return FALSE;
    }


    static function invite($val){
        return filter_var($val, FILTER_VALIDATE_EMAIL);
    }


    static function lang($val){
        return isset(self::VALID_LANG[$val]) ? $val : FALSE;
    }


    static function msg($val){
      if (self::strlen($val, 200)){
        return htmlspecialchars($val, self::HSC_FLAGS, "UTF-8");
      }
      return FALSE;
    }


    static function name($val){
      if (self::strlen($val, 30)){
        return htmlspecialchars($val, self::HSC_FLAGS, "UTF-8");
      }
      return FALSE;
    }


    static function parity($val){
        return isset(self::VALID_PARITY[$val]) ? $val : FALSE;
    }


    static function PID($val){
      if (self::strlen($val, 64, 64)
      && preg_match("#^[a-z0-9]+$#", $val)){
        return $val;
      }
      return FALSE;
    }


    static function phone($val){
        return strlen($val) <= 20 ? $val : FALSE;
    }


    static function placeID($val){
      if (self::strlen($val, 500)
      && preg_match("#^[a-zA-Z0-9_-]+$#", $val)){
        return $val;
      }
      return FALSE;
    }


    static function pushKey($val){
      if (self::strlen($val, 1024)
      && preg_match("#^[a-zA-Z0-9:\/=._+-]+$#", $val)){
        return $val;
      }
      return FALSE;
    }


    static function pushToken($val){
      if (self::strlen($val, 128)
      && preg_match("#^[a-zA-Z0-9\/_+-]+==$#", $val)){
        return $val;
      }
      return FALSE;
    }


    static function qwik($val){
        return isset(self::VALID_QWIK[$val]) ? $val : FALSE;
    }


    static function token($val){
      if (self::strlen($val, 10, 10)
      && preg_match("#^[a-zA-Z0-9]+$#", $val)){
        return $val;
      }
      return FALSE;
    }


    static function venuename($val){
      if (self::strlen($val, 100)
      && mb_ereg_match("[\w\- _&,.]+$", $val)){
        return htmlspecialchars($val, self::HSC_FLAGS, "UTF-8");
      }
      return FALSE;
    }
    
    
    static function vid($val){
      if (self::strlen($val, 150, 8)
      && mb_ereg_match("([\w\- _&,.]+[|]){3}[A-Z]{2}$", $val)){
        return htmlspecialchars($val, self::HSC_FLAGS, "UTF-8");
      }
      return FALSE;
    }
    
    
    static function region($val){
      if (self::strlen($val, 100, 2)
      && mb_ereg_match("^([\w\- _&,.]+[|]){0,2}[A-Z]{2}$", $val)){
        return htmlspecialchars($val, self::HSC_FLAGS, "UTF-8");
      }
      return FALSE;
    }


    static function strlen($val, $max=2048, $min=0){
        $len = strlen($val);
        return ($len >= $min) && ($len <= $max);
    }
    
    
    /**************************************************************************
     * At the time of writing the only html legitimately sent to qwikgame is
     * thru json calls with html extracted from html served by qwikgame.
     * Also, the html is only replicated and returned (never stored in xml)
     * However the html is validated as an protection against xss
     *************************************************************************/
    static function html($val){
      $hash = hash('sha256', $val);
      if(isset(self::HTML_SHA256[$hash])){
        return $val;
      } else {
        self::logMsg("Include hash of authentic html in class/Filter.php\n\t$hash");
      }
      return "<div><p>$hash</p></div>";
    }
    

}


?>


