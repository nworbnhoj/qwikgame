<?php


require_once 'PhraseBook.php';

class Translation extends PhraseBook {

    public function __construct($fileName='translation.xml'){
        parent::__construct($fileName);
    }


    public function save(){
        return self::writeXML(
            $this->xml,
            PATH_LANG,
            $this->fileName
        );
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
