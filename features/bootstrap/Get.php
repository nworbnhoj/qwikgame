<?php

require_once 'class/MatchPage.php';
require_once 'class/FavoritePage.php';


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


  public function __construct(){ }

}


?>
