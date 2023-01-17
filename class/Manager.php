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
        if (isset($mid)){
            $this->xml->addAttribute('vid', $mid);
            return TRUE;
        }
        return FALSE;
    }


    public function venue(){
        $vid = (string) $this->xml['vid'];
        return new Venue($vid);
    }


    public function authURL($shelfLife, $target='facility.php', $param=NULL){
        return parent::authURL($shelfLife, $target, $param);
    }

    
    public function authLink($shelfLife, $target='facility.php', $param=NULL){
        return parent::authLink($shelfLife, $target, $param);
    }


    public function emailWelcome($email, $req, $target='facility.php'){
        return parent::emailWelcome($email, $req, $target);
    }

    
    public function matchID($id){
        return $this->venue()->match($id);
    }

}


?>