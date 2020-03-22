<?php


require_once 'Qwik.php';

class Service extends Qwik {

    private $name;
    private $fileName;
    private $service;
    private $url;
    private $key;

    public function __construct($name, $fileName='services.xml'){
        parent::__construct();
        $this->name = $name;
        $this->fileName = $fileName;
        $this->read();
    }


    private function read(){
        $xml = self::readXML(PATH_SERVICE, $this->fileName);
        if ($xml === FALSE){
            return FALSE;
        }
        $name = $this->name;
        $this->service = $xml->xpath("/services/service[@name='$name']")[0];

        $urls = $this->service->xpath("url[@type]");
        foreach($urls as $url){
            $type = (string) $url['type'];
            $this->url[$type] = (string) $url;
        }

        $this->key['private'] = (string) $this->service->key->private;
        $this->key['public'] = (string) $this->service->key->public;
        return $xml;
    }


    private function save(){
        $xml = self::readXML(PATH_SERVICE, $this->fileName);
        return self::writeXML(
            $xml,
            PATH_LANG,
            $this->fileName
        );
    }


    public function url($type){
        return $this->url[$type];
    }


    public function key($type){
        return isset ($this->key["$type"]) ? $this->key["$type"] : NULL ;
    }   

}

?>
