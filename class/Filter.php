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
   'b19962b0a00b9826f5a6c6298c97cf35a01bda29abcd08a07d2e25a2b268acf7' => 'en cancelled.match.listing',
   '7b7bbf074df104c3d9dbb4226277805844663609adabd81d281ddb923a909f1d' => 'en feedback.match.listing',
   '9c003358e60f8dbe9aed86162af0abae71a63c987ade22ca6978767fbfb2688a' => 'en confirmed.match.listing',
   'e41e0ee6f7fb914277832c0eca98d3dd80b1ed723cf178c064323618b561a483' => 'en accepted.match.listing',
   '72a9f57688b9f8dfdceacb6186822740f46c54ca90c899eed1932e3d25e09495' => 'en invitation.match.listing',
   '2e79a7ab1cf6cf3d031393976c3f56e84161514e345a0c8d4740013c0d8c26b5' => 'en keen.match.listing',
   '562f4c9c6b8771608e26f4c8dc9651e472d2af09bbcee480c9a3cab30efc2c3e' => 'en history.match.listing',
   '8fa7d269710271ec6b5a85e17226b08be5f1eef2d3b5bbfbc86ed385610157b1' => 'en favorite.listing',
   'ca85d45848766c8ca5fba715bcc89aea14de66e9a7d1befaeb2bd9a6e1b5d763' => 'en friend.listing',
   '2d119431c4dea14edd458729e9c151dbfd5854860f490e3822e252ab1ea90ada' => 'en upload.listing',
   
   'dfec5572448c9ce067977d074f116848d03e41a931fbf232d53d081185c8d6a8' => 'bg cancelled.match.listing',
   '53091917efc7f63b3a5a9a7813a2b32dd333a34f5f0c619b07d537add1f3800d' => 'bg feedback.match.listing',
   '3cfd6ec66e6c30082b505ad6ef90da8e0e99181e941b85c621cb2fc420a7c290' => 'bg confirmed.match.listing',
   'befc5bb909e5dd77abc5eec4eaf3e894c6bbc3dbbfee2359007899e5382e9dca' => 'bg accepted.match.listing',
   '59fbdb5536dba674abdbb2f0eb587c4f91e25dd50e456176c56414a9049a7a9b' => 'bg invitation.match.listing',
   '40d61f8fcfcab1168b0ff7c36f4874211c460971ba5258315939bb5be434d36c' => 'bg keen.match.listing',
   '3d8c86725654f96240ba96d20c63ac0484aaf62d5ecaaf3ea0a58bd2c77a94db' => 'bg history.match.listing',
   '14aef2daa71444ebe139a54c47e4b60c8031ba15e109b3700ec411de22d15a83' => 'bg favorite.listing',
   '5420e786167832840eef12aa4a5e0bcf74147d98dc674026e39ffee5a1d714f4' => 'bg friend.listing',
   '77b7b1b7015c2e7707283d2a4480c992c18f45ef429be6268f3a86f3bbb2631b' => 'bg upload.listing',
   
   '149d83ab8de7ba10946c7d37f0649cdc12497f6c6482f97409e5b35a6d64fba6' => 'es cancelled.match.listing',
   '0798aced7ff176db6c81090f09ed205312293a448c5a17b9bc4a8f0c8c77e66c' => 'es feedback.match.listing',
   '31a30d8ebea5f1d34c13867e08239b5e4e79298b8740af71ce63de171b3f5fc0' => 'es confirmed.match.listing',
   'd0bf3de1babe11053984c7407adf188d3c908a9af974b2f9cc8129efcbdf8bbc' => 'es accepted.match.listing',
   '238b41e7dcfcf293911a5412d370b335a2464c72d136386e230b37559f1c7ddc' => 'es invitation.match.listing',
   '2002c9b11068893f008ae7f7734769b94a04cb57ace5d96b58322a293c506598' => 'es keen.match.listing',
   'e88db53778d650bb1f3d97d1f287bd8eb26ce1738177b442bbca139e9035ba96' => 'es history.match.listing',
   '86723de66b2aac45596f3645a8967e0a51d75df8b8b026973e07a4b1b1a21dd8' => 'es favorite.listing',
   '7d81d4df17996bf5f75a79af9ec92d88f6c9af676ab99bcc6dff0edba13f3a79' => 'es friend.listing',
   'cfe8e0307699efcd97be6c6b07368cb9566641d1cd031a9e73c6bdd38478845d' => 'es upload.listing',
   
   '6c688bb40fef41bb4c001df58e0427875329cfde48a16781d1a68520f6e093db' => 'zh cancelled.match.listing',
   'f89094d8f9ba84829bd034edecd53664628ec0b71084d60649ed4f843eec42fb' => 'zh feedback.match.listing',
   'cc01603abdb6a1d3e87992dcb7918584751177f10b3288c1ce22f04ce50dedc5' => 'zh confirmed.match.listing',
   '38b9be293314eba04734863242e04cf3f9968e1bf7cd641de66549bd557ab06b' => 'zh accepted.match.listing',
   '1a56fa7856acf0c2446823fe071365684f0781c818ab14727202b9237e07beab' => 'zh invitation.match.listing',
   'db7cffb3f6b3eaf63a4be8525fcefe2adb4aacd8a01e608b0c8c172c62bbb0ea' => 'zh keen.match.listing',
   'ba57326428d6b77086efdb0a875679139e51896dae4a416c429946d4c826d087' => 'zh history.match.listing',
   '8fa7d269710271ec6b5a85e17226b08be5f1eef2d3b5bbfbc86ed385610157b1' => 'zh favorite.listing',
   'ca85d45848766c8ca5fba715bcc89aea14de66e9a7d1befaeb2bd9a6e1b5d763' => 'zh friend.listing',
   '2d119431c4dea14edd458729e9c151dbfd5854860f490e3822e252ab1ea90ada' => 'zh upload.listing'
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


