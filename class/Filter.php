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
          'undo'=>'',
          'upload'=>'',
          'quit'=>''
    );
    
    
  
  
  // white-list of sha256 hashes of html snippets submitted by json for replication
  // Update when <div class='json'> changes OR included translations change
  const HTML_SHA256 = array(
'e7e2d8cd6b189e1a0a7dd0f7639d062e29e8083b8a7575dee5d6da43ace883ae' => 'ru cancelled.match',
'7b7bbf074df104c3d9dbb4226277805844663609adabd81d281ddb923a909f1d' => 'ru feedback.match',
'59d4759253b9e903a9a5f2c5dc8841f1c4ba8a0c575117d56dd29640c340f9d4' => 'ru confirmed.match',
'c7c8c0d1656546cbf539dd85b3bcac8dc9710ef2084cc3236d99d713cf5bd8fd' => 'ru accepted.match',
'8a43937fadc92086146dd017b2f4b0d5693d410ea4db358d1f7f1a13309c4d16' => 'ru invitation.match',
'adcb3d007eb2fa8e6d434a94105469631e683704e36b37f2398c34a84f0c8536' => 'ru keen.match',
'4422fd02976fdf4d875b4a7203762b64b720cd60c73f532c0df39e47aeba13e8' => 'ru history.match',
'64762f13dba6a051be9565eb990194a9784ea066fe1e740db757e5e73de4e3c6' => 'ru friend',
'e7e2d8cd6b189e1a0a7dd0f7639d062e29e8083b8a7575dee5d6da43ace883ae' => 'jp cancelled.match',
'7b7bbf074df104c3d9dbb4226277805844663609adabd81d281ddb923a909f1d' => 'jp feedback.match',
'59d4759253b9e903a9a5f2c5dc8841f1c4ba8a0c575117d56dd29640c340f9d4' => 'jp confirmed.match',
'8ae6e80dd337fed540797ee96ec44a46ab8bfff9bc5b39eca5b5310eb9bdbfe9' => 'jp accepted.match',
'7945fa0b887141300f2a50c0cf6193417714959e1403b9d6735a74a3b1c64fa2' => 'jp invitation.match',
'917ce7c4034eece2b68b38f172e9e4e8ebfb4616f1cf7dd611e42a14641802cb' => 'jp keen.match',
'4422fd02976fdf4d875b4a7203762b64b720cd60c73f532c0df39e47aeba13e8' => 'jp history.match',
'64762f13dba6a051be9565eb990194a9784ea066fe1e740db757e5e73de4e3c6' => 'jp friend',
'e7e2d8cd6b189e1a0a7dd0f7639d062e29e8083b8a7575dee5d6da43ace883ae' => 'ar cancelled.match',
'7b7bbf074df104c3d9dbb4226277805844663609adabd81d281ddb923a909f1d' => 'ar feedback.match',
'59d4759253b9e903a9a5f2c5dc8841f1c4ba8a0c575117d56dd29640c340f9d4' => 'ar confirmed.match',
'b22fa8cb536869a298c5f36e5e4dabfa10473800fafe68615755618e12407ac9' => 'ar accepted.match',
'3156127908f377aac769895b328abf95fc3425589fcbb6c6820cf24f0e22f399' => 'ar invitation.match',
'713ec85d69f0f383568a67929fca14bb397c12f6ee6fd6ac953c8ee477f9ca07' => 'ar keen.match',
'4422fd02976fdf4d875b4a7203762b64b720cd60c73f532c0df39e47aeba13e8' => 'ar history.match',
'64762f13dba6a051be9565eb990194a9784ea066fe1e740db757e5e73de4e3c6' => 'ar friend',
'e1ecd69665bd0d8aaf90cfb19352acb64388f19ef250a86eee6f59bec59c69fd' => 'zh cancelled.match',
'7b7bbf074df104c3d9dbb4226277805844663609adabd81d281ddb923a909f1d' => 'zh feedback.match',
'59d4759253b9e903a9a5f2c5dc8841f1c4ba8a0c575117d56dd29640c340f9d4' => 'zh confirmed.match',
'f5124d3175e95605d6acf8c9f89ea34d4eebf70cbd64838cd4157c23013632ad' => 'zh accepted.match',
'7945fa0b887141300f2a50c0cf6193417714959e1403b9d6735a74a3b1c64fa2' => 'zh invitation.match',
'efa5814d0c01541f0640b24dd2c3ac1c3526168bcbe13cf9dae7beaa65ab4c64' => 'zh keen.match',
'4422fd02976fdf4d875b4a7203762b64b720cd60c73f532c0df39e47aeba13e8' => 'zh history.match',
'aa68136e2b6633d4e040eb65c0e83ec93f631aefc1fb425cdecb7a46c9093c40' => 'zh friend',
'85615940194cd310862e92782780e74edd50b2611ffe3f448e06a29317c989bf' => 'bg cancelled.match',
'53091917efc7f63b3a5a9a7813a2b32dd333a34f5f0c619b07d537add1f3800d' => 'bg feedback.match',
'11b2cd12cc9ae407fea1189f6d665e4f16a48bd3b753c495e529c181948b50b2' => 'bg confirmed.match',
'17650597e7f155d6b7a9572acfce35e847e94064374e9c4e8db8318dc8e84103' => 'bg accepted.match',
'd6cba3019b56b43f909447dfcb1403722fb62122b7a106688cec5e1d975d15f7' => 'bg invitation.match',
'ce7deb022520e3963a4a8dd3c03724b1aaa4659bf19d41c328c943c386992642' => 'bg keen.match',
'9b32160f5e930a085502eb2a4e8526ae0ba1894b01316c1a4ed27092c8ddb984' => 'bg history.match',
'49b566fceecca7b62fe50c6989c5325577ccafe7994b629047b187c5af750685' => 'bg friend',
'e7e2d8cd6b189e1a0a7dd0f7639d062e29e8083b8a7575dee5d6da43ace883ae' => 'en cancelled.match',
'7b7bbf074df104c3d9dbb4226277805844663609adabd81d281ddb923a909f1d' => 'en feedback.match',
'59d4759253b9e903a9a5f2c5dc8841f1c4ba8a0c575117d56dd29640c340f9d4' => 'en confirmed.match',
'e63cf449e6c60e2b3c08bdf279dbafb70a6e0ed56dbfdc70cf59448fa9f4170c' => 'en accepted.match',
'e2ac40ba2e7db39e62560979c5c8b5351a7af57d3a361f1451e23a73bc1d5cc5' => 'en invitation.match',
'83a2e5b3cf83b76c1cf2086039ea1ba547195ed0235eb1642be4a8c6e8fdc14c' => 'en keen.match',
'4422fd02976fdf4d875b4a7203762b64b720cd60c73f532c0df39e47aeba13e8' => 'en history.match',
'64762f13dba6a051be9565eb990194a9784ea066fe1e740db757e5e73de4e3c6' => 'en friend',
'd3eafeeeab9513c26e6b630557381c153a31fa297f0f12955955b48c7e8fd37c' => 'es cancelled.match',
'0798aced7ff176db6c81090f09ed205312293a448c5a17b9bc4a8f0c8c77e66c' => 'es feedback.match',
'cd5689612acb609c81bf63582ebcde74ea85b4258ea1cae9808d4b7380549ecb' => 'es confirmed.match',
'ab6586b3d1fb07ec6dd7d1b2610ff2e500206d98c68f09f623b546921f69aeb7' => 'es accepted.match',
'8f95268edac5d3f567b6006948bfcf1bbbcba15398a701b233b15a315062007d' => 'es invitation.match',
'9edcbce1a100efe051c4d4e97118343dda756567c4594157aa1ee6f154ab983d' => 'es keen.match',
'a72e5143d2d84fdf9649ded9b936a65247c3a4b3c83d641833ea77ba1a9c6167' => 'es history.match',
'0299ec0e14cd144bad1b6b44a70188e8634ca6a410f23afa776789462d7eaf29' => 'es friend',
'e7e2d8cd6b189e1a0a7dd0f7639d062e29e8083b8a7575dee5d6da43ace883ae' => 'hi cancelled.match',
'7b7bbf074df104c3d9dbb4226277805844663609adabd81d281ddb923a909f1d' => 'hi feedback.match',
'59d4759253b9e903a9a5f2c5dc8841f1c4ba8a0c575117d56dd29640c340f9d4' => 'hi confirmed.match',
'a43399d0d4cf6c05f2f54053aa72c270fd22b772f5e534179e2d8a18c6d1ad76' => 'hi accepted.match',
'3f11c3381434bcfb5a7e50733f7795eec72ddf00d411fd4c9ad7a0e4716431df' => 'hi invitation.match',
'dc94de29b2edc0ed357d74b0ecfb593c891c57695f0338bb64d2a31d45b77262' => 'hi keen.match',
'4422fd02976fdf4d875b4a7203762b64b720cd60c73f532c0df39e47aeba13e8' => 'hi history.match',
'64762f13dba6a051be9565eb990194a9784ea066fe1e740db757e5e73de4e3c6' => 'hi friend',
'e7e2d8cd6b189e1a0a7dd0f7639d062e29e8083b8a7575dee5d6da43ace883ae' => 'fr cancelled.match',
'7b7bbf074df104c3d9dbb4226277805844663609adabd81d281ddb923a909f1d' => 'fr feedback.match',
'4b95e315060b301abfa21ce31308a55d3a932a17486af004ffbee4d6c7b736c1' => 'fr confirmed.match',
'cbb8df9b2b7306e55c1d4b3fa8af257b1cd4f88a326a51fe6abee6ea55f31cad' => 'fr accepted.match',
'b7b9c9a829bdc60ed93c643889e84f1354b5ca060b7ae17a89c9d350aa07e792' => 'fr invitation.match',
'454a5e4420c28429c8787b97f645a2f8835d840f8ee90dd7f7d8474b15d0bf4f' => 'fr keen.match',
'4422fd02976fdf4d875b4a7203762b64b720cd60c73f532c0df39e47aeba13e8' => 'fr history.match',
'64762f13dba6a051be9565eb990194a9784ea066fe1e740db757e5e73de4e3c6' => 'fr friend'
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


