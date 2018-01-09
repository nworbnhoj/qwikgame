<?php

require_once 'Qwik.php';
require_once 'Translation.php';
require_once 'Venue.php';

class Html extends Qwik {

    static $translation;

    static $languages = array(
        'zh'=>'中文',
        'es'=>'Español',
        'en'=>'English',
        // 'fr'=>'français',
        // 'hi'=>'हिन्दी भाषा',
        // 'ar'=>'اللغة العربية',
        // 'jp'=>'日本語'
    );


    const QWIK_URL   = 'http://' . self::SUBDOMAIN . '.qwikgame.org';
    const TERMS_URL  = self::QWIK_URL.'/pdf/qwikgame.org%20terms%20and%20conditions.pdf'; 
    const PRIVACY_URL  = self::QWIK_URL.'/pdf/qwikgame.org%20privacy%20policy.pdf';
    const TERMS_LNK    = "<a href='".self::TERMS_URL."' target='_blank'>{Terms and Conditions}</a>";
    const PRIVACY_LNK    = "<a href='".self::PRIVACY_URL."' target='_blank'>{Privacy Policy}</a>";

    private $language;

    public function __construct($language = 'en'){
        parent::__construct();
        $this->language = $language;
    }


    public function language($language=NULL){
        if(isset($language)){
            $this->language = $language;
        }
        return $this->language;
    }


    function translation(){
        if (is_null(self::$translation)){
            self::$translation = new Translation('translation.xml', 'lang');
        }
        return self::$translation;
    }


    public function variables(){
        $vars = array(
            'homeURL'       => self::QWIK_URL,
            'termsURL'      => self::TERMS_URL,
            'privacyURL'    => self::PRIVACY_URL,
            'termsLink'     => self::TERMS_LNK,
            'privacyLink'   => self::PRIVACY_LNK,
        );
        
        return $vars;
    }


    public function html($html){
        $html = $this->populate($html, $this->variables());
        $html = $this->translate($html, $this->language());
        return $html;
    }


    public function languages(){
        return self::translation()->languages();
    }


    /********************************************************************************
    Return the html template after replacing {variables} with the requested
    language (or with the fallback language as required)

    $html    String    html template with variables of the form {name}
    $lang    String    language to replace {variables} with
    $fb      String    fallback language for when a translation is missing
    ********************************************************************************/
    public function translate($html, $lang, $fb='en'){
        $translation = self::translation();
        $pattern = '!(?s)\{([^\}]+)\}!';
        $tr = function($match) use ($translation, $lang, $fb){
            $key = $match[1];
            $phrase = $translation->phrase($key, $lang, $fb);
            return empty($phrase) ? '{'."$key".'}' : $phrase;
        };
        return  preg_replace_callback($pattern, $tr, $html);
    }


    /********************************************************************************
    Return the html template after replacing [variables] with the values provided.

    $html        String        html template with variables of the form [key]
    $variables    ArrayMap    variable name => $value
    ********************************************************************************/
    public function populate($html, $variables){
        $pattern = '!(?s)\[([^\]]+)\]!';
        $tr = function($match) use ($variables){
            $m = $match[1];
            return isset($variables[$m]) ? $variables[$m] : "[$m]";
        };
        return  preg_replace_callback($pattern, $tr, $html);
    }





    function repStr($word){
        return empty($word) ? 'AAAAAA' : " with a $word reputation";
    }



    static public function parityStr($parity){
//echo "<br>PARITYSTR $parity<br>";
        if(!is_numeric("$parity")){
            return '';
        }

        $pf = floatval($parity);
        if($pf <= -2){
            return "{much_weaker}";
        } elseif($pf <= -1){
            return "{weaker}";
        } elseif($pf < 1){
            return "{well_matched}";
        } elseif($pf < 2){
            return "{stronger}";
        } else {
            return "{much_stronger}";
        }
    }


    private function trim_value(&$value)
    {
        $value = trim($value);
    }


    private function venueLink($vid){
        $name = explode("|", $vid)[0];
        $boldName = $this->firstWordBold($name);
        $url = self::QWIK_URL."/venue.php?vid=$vid";
        $link = "<a href='$url'>$boldName</a>";
        return $link;
    }


    private function firstWordBold($phrase){
        $words = explode(' ', $phrase);
        $first = $words[0];
        $words[0] = "<b>$first</b>";
        return implode(' ', $words);
    }

}

?>
