<?php

require_once 'class/MatchPage.php';
require_once 'class/FavoritePage.php';
require_once 'class/FacilityPage.php';


class Get {
  


  static function matchPage($pid, $token){
    $_POST = array();
    $_GET = array(
      'qwik'   => 'login',
      'pid'    => $pid,
      'token'  => $token
    );
    $page = new MatchPage();
    return $page->processRequest();
  }


  static function favoritePage($get){
    $_POST = array();
    $_GET = $get;
    $page = new FavoritePage();
    return $page->processRequest();
  }


  static function facilityPage($get){
    $_POST = array();
    $_GET = $get;
    $page = new FacilityPage();
    return $page->processRequest();
  }


  static function bookingPage($get){
    $_POST = array();
    $_GET = $get;
    $page = new BookingPage();
    return $page->processRequest();
  }


  public function __construct(){ }

}


?>