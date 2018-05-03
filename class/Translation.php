<?php


require_once 'Qwik.php';

class Translation extends Qwik {

    private $fileName;
    private $xml;
    private $phrases = array();
    private $languages = array();

    public function __construct($fileName='translation.xml'){
        parent::__construct();
        $this->fileName = $fileName;
        $this->xml = self::readXML(Qwik::PATH_LANG, $fileName);
        
        $xmlPhrases = $this->xml->xpath("phrase");
        foreach($xmlPhrases as $xmlPhrase){
            $key = (string) $xmlPhrase['key'];
            if(!is_null($key)){
                $phrase = array();
                foreach($xmlPhrase->children() as $child){
                    $phrase[$child->getName()] = (string) $child;
                }            
                $this->phrases[$key] = $phrase;
            }
        }
        
        $xmlLanguages = $this->xml->xpath("language");
        foreach($xmlLanguages as $xmlLanguage){
            $key = (string) $xmlLanguage['key'];
            if(!is_null($key)){   
                $this->languages[$key] = html_entity_decode((string) $xmlLanguage);
            }
        }      
    }


    public function save(){
        return self::writeXML(
            $this->xml,
            Qwik::PATH_LANG,
            $this->fileName
        );
    }


    public function phrase($key, $lang, $fallback='en'){
        if (array_key_exists($key, $this->phrases)){
            $phrase = $this->phrases[$key];
            if (array_key_exists($lang, $phrase)){
                return $phrase[$lang];
            } elseif (array_key_exists($fallback, $phrase)){
                return $phrase[$fallback];
            }
        }
        return NULL;
    }


    public function set($key, $lang, $phrase){
        if (array_key_exists($key, $this->phrases)){
            $phraseElement = $this->xml->xpath("phrase[@key='$key']")[0];
        } else {
            $this->phrases[$key] = array();
            $phraseElement = $this->xml->addChild('phrase');
            $phraseElement['key'] = $key;
        }

        $this->phrases[$key][$lang] = $phrase;
        $langElement = $phraseElement->xpath($lang)[0];
        if(isset($langElement)){
            $langElement = $phrase;
        } else {
            $phraseElement->addChild($lang, $phrase);
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
    
    
    public function export(){
        $xml = new SimpleXMLElement("<translation></translation>");
    
        foreach($this->languages as $key => $native){
            $element = $xml->addChild('language');
            $element->addAttribute($key, $native);        
        }

        foreach($this->phrases as $key => $phrase){            
            $phraseElement = $xml->addChild('phrase');
            $phraseElement->addAttribute('key', $key);
            foreach ($phrase as $lang => $dict){
                $phraseElement->addChild($lang, $dict);
            }     
        }
        $xml->saveXML("translation1.xml");
    }

}

?>
