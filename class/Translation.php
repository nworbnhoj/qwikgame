<?php

class Translation {

    private $xml;
    private $phrases = array();
    private $languages = array();

    public function __construct($filename='translation.xml'){
        $this->xml = simpleXML_load_file($filename);
        
        $xmlPhrases = $this->xml->xpath("phrase");
        foreach($xmlPhrases as $xmlPhrase){
            $key = (string) $xmlPhrase['key'];
            if(!is_null($key)){
                $phrase = array();
                foreach($xmlPhrase as $lang => $trans){
                    if($lang !== 'key'){
                        $phrase[$lang] = $trans;
                    }
                }            
                $this->phrases[$key] = $phrase;
            }
        }
        
        $xmlLanguages = $this->xml->xpath("language");
        foreach($xmlLanguages as $xmlLanguage){
            $key = (string) $xmlLanguage['key'];
            if(!is_null($key)){
                $this->languages[$key] = (string) $xmlLanguage;
            }
        }      
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
        return null;
    }
    
    
    public function languages(){
        return $this->languages;
    }
    
    
    public function phraseKeys(){
        return array_keys($this->phrases);
    }


}

?>
