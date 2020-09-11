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
          'friend'=>'',
          'keen'=>'',
          'login'=>'',
          'logout'=>'',
          'msg'=>'',
          'recover'=>'',
          'region'=>'',
          'register'=>'',
          'translate'=>'',
          'upload'=>''
    );
    
    
  
  
  // white-list of sha256 hashes of html snippets submitted by json for replication
  // Update when <div class='json'> changes OR included translations change
  const HTML_SHA256 = array(
   '3f10caa428381115e63227801b26efa7715725c59f8c95b73a01631958f8f67c' => 'en cancelled.match.listing',
   '2792a518ae8a60659bc2db31eee5afc953f914353b8ca1dfd13bf8b9bdb19590' => 'en feedback.match.listing',
   '54e1a24774ccb7ad5a849afb9ce188b6a6933a2089ae4523ec6aa8b5c8128a13' => 'en confirmed.match.listing',
   '95c04b6870a02d7c8abb897ff595f0e0d8c0bb6137deb186b90f21a4bb4b9d13' => 'en accepted.match.listing',
   '17e564077ed1e14c6054f4a66ae1897ceb768b912925ad16f6e0331cef9c7639' => 'en invitation.match.listing',
   '9074eaa56d507618548565d89a634383fdfff2e14200cac0ca3d0b0df0b5cc93' => 'en keen.match.listing',
   'dfbfe806cf21f0697fbea049e2e24a56b026e3dfb5eb92852e84c1af41be19b7' => 'en history.match.listing',
   '8b4debe466109d2551a53bfeb2ff02cda75425df596f7fc74705d03dea36c33d' => 'en favorite.listing',
   '041594c6847191044f6c2a9e755233761993152788a5df5b0221cd4247f3efa8' => 'en friend.listing',
   '707cbeb51fabf3207c08aba7afb79c46d7d77050bd5c784b2a20ad1684c6d670' => 'en upload.listing',
   
   'b20c03536f349802db8c8c8ddbd70c67567b617060745b05161076e488f00609' => 'bg cancelled.match.listing',
   '01b0828e91909ffdf9c912f1af91b6deabff074f70ca0a3a2cb3fc8f61288824' => 'bg feedback.match.listing',
   'ee056390611597569375dd0d9eb75ecc05af81f648d29c225106be6bae4c25d7' => 'bg confirmed.match.listing',
   'df89049f0566b5ad826d930c7d02b27f9c992759a1628aa025434c19bfd8cda6' => 'bg accepted.match.listing',
   '663b902812cec7547b9661f734966184e7663724ccf05346de4293f994e8b6d4' => 'bg invitation.match.listing',
   'a3d99d6662e035c123539392b7035c288b46f00bc0b801bfb143db3a35f97eaa' => 'bg keen.match.listing',
   'c191df15129bc0597c71c185f0315136b692e49bce74a13641c4dc663408ec12' => 'bg history.match.listing',
   '8f1875b9fa6907c1e21f14c5221a72d5cad7acb39e666fb183217f264fc83855' => 'bg favorite.listing',
   '34c83baa98909de55fee6cd76bd35b44d56c5235f6388002f9d129db259a0498' => 'bg friend.listing',
   '9cc856785492983edb457c008db5dd493f62efb519427cca199de1ae82c36baa' => 'bg upload.listing',
   
   'b79ec9a322281f994025303c4bcc9659a8dd898bbc82f8d4b0b6392b952268b5' => 'es cancelled.match.listing',
   'f89094d8f9ba84829bd034edecd53664628ec0b71084d60649ed4f843eec42fb' => 'es feedback.match.listing',
   'b47c483ad349135114478fb3ac1d42d2359206a52f2362a0449abd514c1509a2' => 'es confirmed.match.listing',
   'c1ecb752c5758740a64daf27ee9a5bb882797c627be5ad58d45c5dd8a763ee5e' => 'es accepted.match.listing',
   '3c57530c604f6e0dc323f9aebef9e47ce16a51c14634195f0bc25ced3b32caa4' => 'es invitation.match.listing',
   'fdcf3fd577a7fa8a56ab00dff7bbb13f35a8f592ab7864435d813ff1dd616f1b' => 'es keen.match.listing',
   'ba57326428d6b77086efdb0a875679139e51896dae4a416c429946d4c826d087' => 'es history.match.listing',
   'd080f072fe751b57210a1d136b8d010c87f321248131a28b4f114a9e7778a2ed' => 'es favorite.listing',
   '8294a5fd8e4d2076de711ed9d6c3fa4cbc00454fc4214e5556e233b69cfff629' => 'es friend.listing',
   '2b8bb305d84b53f5cf20a28d16835653a0a1395f58a52fd0da2ed93208416cde' => 'es upload.listing',
   
   '13aba90b4d3db853597085394b1962ffd7c8d6d75c335b1be6c38efba0fda99c' => 'zh cancelled.match.listing',
   'f89094d8f9ba84829bd034edecd53664628ec0b71084d60649ed4f843eec42fb' => 'zh feedback.match.listing',
   'b47c483ad349135114478fb3ac1d42d2359206a52f2362a0449abd514c1509a2' => 'zh confirmed.match.listing',
   'dff970ddb234fd472a4d8c4a95a1e812c1879da9fcbad9827688f01a761d89ae' => 'zh accepted.match.listing',
   '0e3cc8edc88c2912b304de136e7ab257201fc364566a5908bb6d480c1ea34532' => 'zh invitation.match.listing',
   '35db39bf079816ed55f3ffc55f837e0502f14ca5ff45c8a476ae9076d577fdde' => 'zh keen.match.listing',
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
    const HONEYPOT= array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::honeypot');
    const HOURS   = array('filter'=>FILTER_VALIDATE_INT,   'options'=>Filter::OPT_HRS);
    const HTML    = array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::html');
    const FILENAME= array('filter'=>FILTER_CALLBACK,       'options'=>'Filter::filename');
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
      && mb_ereg_match("(([\w\- _&,]+[|]){0,3}[A-Z]{2}:?)+$", $val)){
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


    static function filename($val){
      if (self::strlen($val, 30)
      && preg_match("#^[a-zA-Z0-9_-]+$#", $val)){
        return $val;
      }
      return FALSE;
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
      && preg_match("#^[a-zA-Z0-9_-+]+$#", $val)){
        return $val;
      }
      return FALSE;
    }


    static function pushToken($val){
      if (self::strlen($val, 128)
      && preg_match("#^[a-zA-Z0-9_-+]+==$#", $val)){
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
      && mb_ereg_match("[\w\- _&,]+$", $val)){
        return htmlspecialchars($val, self::HSC_FLAGS, "UTF-8");
      }
      return FALSE;
    }
    
    
    static function vid($val){
      if (self::strlen($val, 150, 8)
      && mb_ereg_match("([\w\- _&,]+[|]){3}[A-Z]{2}$", $val)){
        return htmlspecialchars($val, self::HSC_FLAGS, "UTF-8");
      }
      return FALSE;
    }
    
    
    static function region($val){
      if (self::strlen($val, 100, 6)
      && mb_ereg_match("([\w\- _&,]+[|]){0,2}[A-Z]{2}$", $val)){
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


