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
        $this->xml = self::readXML(PATH_LANG, $fileName);
        
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
            PATH_LANG,
            $this->fileName
        );
    }


    public function phrase($key, $lang, $fallback="en"){
        $phraseArray = $this->xml->xpath("phrase[@key='$key']");
        if($phraseArray){
            $phraseElement = $phraseArray[0];
            $words = $this->words($phraseElement, $lang);
            if (isset($words)){
                return $words;
            } else {
                return $this->words($phraseElement, $fallback);
            }
        }
        return NULL;
    }


    private function words($phraseElement, $lang){
        $wordsArray = $phraseElement->xpath("words[@lang='$lang']");
        if($wordsArray){
            return (string) $wordsArray[0];
        }
        return NULL;
    }


    public function set($key, $lang, $phrase){
        $wordsElement = array();
        $phraseIndex = $this->phraseIndex($this->xml, $key);
        if(isset($phraseIndex)){
            $phraseElement = $this->xml->phrase[$phraseIndex];
            $wordsIndex = $this->wordsIndex($phraseElement, $lang);
            if(isset($wordsIndex)){
                $phraseElement->words[$wordsIndex] = $phrase;
            } else {
                $wordsElement = $phraseElement->addChild("words", "$phrase");
                $wordsElement['lang'] = $lang;
            }
        } else {
            $phraseElement = $this->xml->addChild("phrase");
            $phraseElement["key"] = $key;
            $wordsElement = $phraseElement->addChild("words", "$phrase");
            $wordsElement['lang'] = $lang;
        }
    }


    public function unset($key, $lang){
        $phraseIndex = $this->phraseIndex($this->xml, $key);
        if (isset($phraseIndex)){
            $phraseElement = $this->xml->phrase[$phraseIndex];
            $wordsIndex = $this->wordsIndex($phraseElement, $lang);
            if(isset($wordsIndex)){
                $wordsElement = $phraseElement->words[$wordsIndex];
                unset($this->xml->phrase[$phraseIndex]->words[$wordsIndex]);
//                self::removeElement($this->xml->phrase[$phraseIndex]->words[$wordsIndex]);
            }
        }
    }


    private function phraseIndex($element, $key){
        $count = count($element->phrase);
        for($i=0; $i<$count; $i++){
            if((string)$element->phrase[$i]["key"] === "$key"){
                return $i;
            }
        }
        return NULL;
    }


    private function wordsIndex($element, $lang){
        $count = count($element->words);
        for($i=0; $i<$count; $i++){
            if((string)$element->words[$i]["lang"] === "$lang"){
                return $i;
            }
        }
        return NULL;
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
