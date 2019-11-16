<?php


require_once 'Qwik.php';

class Service extends Qwik {

    private $fileName;
    private $service;
    private $url;
    private $key;

    public function __construct($name, $fileName='services.xml'){
        parent::__construct();
        $this->fileName = $fileName;
        $this->read($fileName);
    }


    private function read($fileName){
        $xml = self::readXML(".", $fileName);
        $this->service = $xml->xpath("service[@name='$name']")[0];
        $this->url['xml'] = (string) $this->service->xml[0];
        $this->_url['json'] = (string) $this->service->json[0];
        $this->key = (string) $this->service->key[0];
        return $xml;
    }


    private function save(){
        $xml = self::readXML(".", $this->fileName);
        return self::writeXML(
            $xml,
            Qwik::PATH_LANG,
            $this->fileName
        );
    }


    public function url($type){
        return $this->url[$type];
    }


    public function key(){
        return $this->key;
    }   

}

?>
