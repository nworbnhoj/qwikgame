<?php

class Translation {

    private $log;
    private $path;
    private $filename;
    private $xml;
    private $phrases = array();
    private $languages = array();

    public function __construct($log, $filename='translation.xml', $path=''){
        $this->log = $log;
        $this->path = $path;
        $this->filename = $filename;
        $this->xml = readXML($path, $filename, $log);
        
        $xmlPhrases = $this->xml->xpath("phrase");
        foreach($xmlPhrases as $xmlPhrase){
            $key = (string) $xmlPhrase['key'];
            if(!is_null($key)){
                $phrase = array();
                foreach($xmlPhrase->attributes() as $lang => $trans){
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
    
    
    public function save(){
        writeXML($this->xml, $this->path, $this->filename, $this->log);
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


    public function set($key, $lang, $phrase){
        if (array_key_exists($key, $this->phrases)){
            $element = $this->xml->xpath("phrase='$key'")[0];
        } else {
            $this->phrases[$key] = array();
            $element = $this->xml->addChild('phrase');
        }

        $this->phrases[$key][$lang] = $phrase;
        if(isset($element[$lang])){
            $element[$lang] = $phrase;
        } else {
            $element->addAttribute($lang, $phrase);
        }
    }


    public function languages(){
        return $this->languages;
    }
    
    
    public function phraseKeys(){
        return array_keys($this->phrases);
    }


}

?>
