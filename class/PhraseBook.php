<?php


require_once 'Qwik.php';

class PhraseBook extends Qwik {

    protected $fileName;
    protected $xml;
    private $phrases = array();
    private $languages = array();

    public function __construct($fileName='translation.xml'){
        parent::__construct();
        $this->fileName = $fileName;
        $this->xml = self::readXML(PATH_LANG, $fileName);
        
        $xmlPhrases = $this->xml->xpath("phrase");
        foreach($xmlPhrases as $xmlPhrase){
            $key = (string) $xmlPhrase['key'];
            if(!empty($key)){
                $phrase = array();
                foreach($xmlPhrase->children() as $child){
                    $lang = (string) $child['lang'];
                    if (!empty($lang)){
                        $phrase[$lang] = html_entity_decode((string) $child);
                    }
                }            
                $this->phrases[$key] = $phrase;
            }
        }
        
        $xmlLanguages = $this->xml->xpath("language");
        foreach($xmlLanguages as $xmlLanguage){
            $key = (string) $xmlLanguage['key'];
            if(!empty($key)){   
                $this->languages[$key] = html_entity_decode((string) $xmlLanguage);
            }
        }      
    }



    /********************************************************************************
     * Return the test after replacing {phrases} in the requested language (or with
     * the fallback language as required)
     *
     * $text    String    html template with variables of the form {name}
     * $lang    String    language to replace {variables} with
     * $fb      String    fallback language for when a translation is missing
     *******************************************************************************/
    public function translate($text, $lang='en', $fb='en'){
        $pattern = '!(?s)\{([^\}]+)\}!';
        $tr = function($match) use ($lang, $fb){
            $key = $match[1];
            $phrase = $this->phrase($key, $lang, $fb);
            return empty($phrase) ? '{'."$key".'}' : $phrase;
        };
        return  preg_replace_callback($pattern, $tr, $text);
    }


    public function phrase($key, $lang, $fallback="en"){
        if (!isset($key, $lang, $this->phrases[$key])) { return NULL; }

        $phrase = $this->phrases[$key];

        if (isset($phrase[$lang])) {
            return $phrase[$lang];
        } else {
            return isset ($phrase[$fallback]) ? $phrase[$fallback] : NULL;
        }
    }


    public function languages(){
        return $this->languages;
    }


    public function phraseKeys(){
        return array_keys($this->phrases);
    }

    
    public function direction($lang){
        $element = $this->xml->xpath("language[@key='$lang']")[0];
        return isset($element) ? $element['dir'] : '';
    }


}

?>
