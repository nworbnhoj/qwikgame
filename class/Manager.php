<?php

require_once 'User.php';


class Manager extends User {

    const DEFAULT_MANAGER_XML = 
   "<?xml version='1.0' encoding='UTF-8'?>
    <manager lang='en' ok='true'>
      <notify/>
    </manager>";
        

    /**
    * @throws RuntimeException if construction fails.
    */
    public function __construct($mid, $forge=FALSE){
        parent::__construct($mid, $forge);
        self::logMsg("manager new $mid");
    }


    public function default_xml(){
        return self::DEFAULT_MANAGER_XML;
    }


    public function setVenue($mid){
        $this->xml->addAttribute('vid', $mid);
    }


    public function venue(){
        $vid = (string) $this->xml['vid'];
        return new Venue($vid);
    }


    public function authURL($shelfLife, $target='booking.php', $param=NULL){
        return parent::authURL($shelfLife, $target, $param);
    }

    
    public function authLink($shelfLife, $target='booking.php', $param=NULL){
        return parent::authLink($shelfLife, $target, $param);
    }


    public function emailWelcome($email, $req, $target='booking.php'){
        return parent::emailWelcome($email, $req, $target);
    }

    
    public function matchID($id){
        $xml = $this->xml->xpath("match[@id='$id']");
        if (is_array($xml) && isset($xml[0])){
            return new Match($this, $xml[0]);
        }
        return NULL;
    }

}


?>
