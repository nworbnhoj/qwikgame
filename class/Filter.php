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
          '+1'=>'',
          '+2'=>''
    );
    const VALID_QWIK = array(
          'accept'=>'',
          'account'=>'',
          'activate'=>'',
          'available'=>'',
          'book' =>'',
          'call' =>'',
          'cancel'=>'',
          'deactivate'=>'',
          'decline'=>'',
          'delete'=>'',
          'facility' =>'',
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
'944bfb57ef2991687ea3fab7e493e8bd532678aada661f6f11c34c1335d169bc' => 'ru cancelled.match',
'7b7bbf074df104c3d9dbb4226277805844663609adabd81d281ddb923a909f1d' => 'ru feedback.match',
'78e4239d2b008b1eb7733abfd62ff4ecb122f0a1ec465609ccd08032b5703aad' => 'ru confirmed.match',
'b4ac954fe2547df6a634999b4783e703ef80dc60064fe4b97d3d7ad3f75aa719' => 'ru accepted.match',
'918fda6acc3727e5a50a940d523524636377143c7f33335ae35cb4757658aa43' => 'ru invitation.match',
'4bb056e108e7e6dcb9387960bf9b0954382d344b9e665b0f51873aaa56010bed' => 'ru keen.match',
'6fe08bd5822dd64f0b08da3b236897b6dc6224da805dfad588887b34486c0977' => 'ru history.match',
'ae7b3523a991c35c5b71e3ab40017f1cb2ecc08e7125fcdfa81354a0574016c9' => 'ru friend',
'944bfb57ef2991687ea3fab7e493e8bd532678aada661f6f11c34c1335d169bc' => 'jp cancelled.match',
'7b7bbf074df104c3d9dbb4226277805844663609adabd81d281ddb923a909f1d' => 'jp feedback.match',
'78e4239d2b008b1eb7733abfd62ff4ecb122f0a1ec465609ccd08032b5703aad' => 'jp confirmed.match',
'91d99e1a0dc93c7f976393149c8df29db88660b55fd39371318b07db62ec74ca' => 'jp accepted.match',
'5f6ee77d05b6f400315b7c5b05e7ffc7b4ae092a4cf96a4dd9b63e38cab76989' => 'jp invitation.match',
'7b2d198561849ec184998b444884b9fe50a352c3fd7b3df5f3a3f02476742e66' => 'jp keen.match',
'6fe08bd5822dd64f0b08da3b236897b6dc6224da805dfad588887b34486c0977' => 'jp history.match',
'ae7b3523a991c35c5b71e3ab40017f1cb2ecc08e7125fcdfa81354a0574016c9' => 'jp friend',
'944bfb57ef2991687ea3fab7e493e8bd532678aada661f6f11c34c1335d169bc' => 'ar cancelled.match',
'7b7bbf074df104c3d9dbb4226277805844663609adabd81d281ddb923a909f1d' => 'ar feedback.match',
'78e4239d2b008b1eb7733abfd62ff4ecb122f0a1ec465609ccd08032b5703aad' => 'ar confirmed.match',
'5ed4dd1b347322969f553594d64535098926accf0939d360aec4f889c67576b7' => 'ar accepted.match',
'd37e3ca0db32bfb5013212272dce7c10abbf8c36c26744613327d2343114144f' => 'ar invitation.match',
'0fe11a4508fe638990886fb5102c77fea42574035501fac8dcc5753eabeec2ab' => 'ar keen.match',
'6fe08bd5822dd64f0b08da3b236897b6dc6224da805dfad588887b34486c0977' => 'ar history.match',
'ae7b3523a991c35c5b71e3ab40017f1cb2ecc08e7125fcdfa81354a0574016c9' => 'ar friend',
'52052e4eeecdd06ce9277fd6876abb7dd709e36f2d2e6aed62859e8020fcf1e0' => 'zh cancelled.match',
'7b7bbf074df104c3d9dbb4226277805844663609adabd81d281ddb923a909f1d' => 'zh feedback.match',
'78e4239d2b008b1eb7733abfd62ff4ecb122f0a1ec465609ccd08032b5703aad' => 'zh confirmed.match',
'2ff38d78a7fcc76b3d25ac18856ac454174379c464c0ecc54cf61946b9eeaab6' => 'zh accepted.match',
'5f6ee77d05b6f400315b7c5b05e7ffc7b4ae092a4cf96a4dd9b63e38cab76989' => 'zh invitation.match',
'88a06caf8ce410a2196c6c775551aba92a7b14712153206a656af0589b7f26e3' => 'zh keen.match',
'6fe08bd5822dd64f0b08da3b236897b6dc6224da805dfad588887b34486c0977' => 'zh history.match',
'839b05221eeabf4f5cf947f5c5791921959ed4dc31ab36208120b51301ca60f0' => 'zh friend',
'4a37e87bb7701d7dde16b16217cdd6474348323799628ce5cd1dd0c532765178' => 'bg cancelled.match',
'53091917efc7f63b3a5a9a7813a2b32dd333a34f5f0c619b07d537add1f3800d' => 'bg feedback.match',
'ccac57b6acb7d4ce7695ea83f3f1852036c043b1a8de94e9dd6d88767c71bbc5' => 'bg confirmed.match',
'26801ee58e65268a2232e65a1142113ab5b87e02cee6295e3960af047ce7f4f5' => 'bg accepted.match',
'596d2e6d7037f799f1ea54551bb6a38fd72d781162a39e8f9bc92cd323d509cb' => 'bg invitation.match',
'cb24a94da304adbd1383549484eefc923b1d24541d1f1d526394f5de914254fe' => 'bg keen.match',
'62b096f9cf82a3a45eeff10f79b407335cfa8b046976afee834717bd1ca56d06' => 'bg history.match',
'429cea943676b2840100f5e6b75b13c513402cb372ae9b67cbf2b1219889d121' => 'bg friend',
'944bfb57ef2991687ea3fab7e493e8bd532678aada661f6f11c34c1335d169bc' => 'en cancelled.match',
'7b7bbf074df104c3d9dbb4226277805844663609adabd81d281ddb923a909f1d' => 'en feedback.match',
'78e4239d2b008b1eb7733abfd62ff4ecb122f0a1ec465609ccd08032b5703aad' => 'en confirmed.match',
'e4f5e85185957ba69b901abb86bf9c13dd9fbdf768ac7c3bb945f0e1b581eb71' => 'en accepted.match',
'653abe44fd74f753c756e0d6849acbf359b4f67e1ff2c61d691cb22367659f1d' => 'en invitation.match',
'02ecb1fdb1aeb03aa67d20808d5e1f4989919a92ad12f94e2b079c125dd33e14' => 'en keen.match',
'6fe08bd5822dd64f0b08da3b236897b6dc6224da805dfad588887b34486c0977' => 'en history.match',
'ae7b3523a991c35c5b71e3ab40017f1cb2ecc08e7125fcdfa81354a0574016c9' => 'en friend',
'2cbc4306a87d2490f9cb96a833872b58a32ad28d1012f90b15ea7770f7c9fc2b' => 'es cancelled.match',
'0798aced7ff176db6c81090f09ed205312293a448c5a17b9bc4a8f0c8c77e66c' => 'es feedback.match',
'ea51664e513aa7f4d40c23354a5db4efe40650b7fe5c5dad972e39a1d8ad1223' => 'es confirmed.match',
'7f909948f463a5c158ee6e845795d7da2825c1f5a5edd43a0c1b4a10aa79f4a0' => 'es accepted.match',
'ff58990ff622da8c3eb84fe6088782ae376e7a63e14afe4c0e63c122ddaedb39' => 'es invitation.match',
'a8f56f4d518ef77ffe90ca4af4836302b4bda73edc13bf4c089a97634d1202b0' => 'es keen.match',
'731821bad03857ad83110529b95eb165d125ddba9c19c89f612fe6bf7de64320' => 'es history.match',
'effbc2da690e5e9f72d580f03d485a7559d989d342d841a8aa1366dd51cfe659' => 'es friend',
'944bfb57ef2991687ea3fab7e493e8bd532678aada661f6f11c34c1335d169bc' => 'hi cancelled.match',
'7b7bbf074df104c3d9dbb4226277805844663609adabd81d281ddb923a909f1d' => 'hi feedback.match',
'78e4239d2b008b1eb7733abfd62ff4ecb122f0a1ec465609ccd08032b5703aad' => 'hi confirmed.match',
'995b3f07789950013ae8d064ac2b52b802058cb089d3ee754f5515ef90c18451' => 'hi accepted.match',
'ff22c947db14e4f3a13b5b10735b1958470e8c2226f24fe95cd4be97ff2dc40c' => 'hi invitation.match',
'd6eb4bd8d68230538fea44b24789c4cc10d0c2b777e86e9e0ab58bea613d209e' => 'hi keen.match',
'6fe08bd5822dd64f0b08da3b236897b6dc6224da805dfad588887b34486c0977' => 'hi history.match',
'ae7b3523a991c35c5b71e3ab40017f1cb2ecc08e7125fcdfa81354a0574016c9' => 'hi friend',
'944bfb57ef2991687ea3fab7e493e8bd532678aada661f6f11c34c1335d169bc' => 'fr cancelled.match',
'7b7bbf074df104c3d9dbb4226277805844663609adabd81d281ddb923a909f1d' => 'fr feedback.match',
'c01f1d6ac53f6886b3e9dc2dc0cdde71965114dd92e6c21faf12178e9626e3fb' => 'fr confirmed.match',
'd0dbb2c0b81f2152e99d7c01b2a00ba97964f6f0e89e982b4690a9e90767e065' => 'fr accepted.match',
'817100dbbb699e3609b98012ffa4eb8d1f79843b91c23e40a4c389cdf8c0baac' => 'fr invitation.match',
'147f4c5db6da6336125abf8ef73d6d37ec2952a082433a953921700b977d9374' => 'fr keen.match',
'6fe08bd5822dd64f0b08da3b236897b6dc6224da805dfad588887b34486c0977' => 'fr history.match',
'ae7b3523a991c35c5b71e3ab40017f1cb2ecc08e7125fcdfa81354a0574016c9' => 'fr friend'
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