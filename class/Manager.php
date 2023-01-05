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
        return DEFAULT_MANAGER_XML;
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

}


?>
