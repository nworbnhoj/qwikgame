<?php


require_once 'class/IndexPage.php';
require_once 'class/MatchPage.php';
require_once 'class/FavoritePage.php';
require_once 'class/FriendPage.php';
require_once 'class/BookingPage.php';


class Post {

    
    
  static function matchPageKeen($pid, $game, $vid, $today, $tomorrow, $invite){
    return self::matchPage($pid, array(
      'qwik'     => 'keen',
      'game'     => $game,
      'vid'      => $vid,
      'today'    => $today,
      'tomorrow' => $tomorrow,
      'invite'   => $invite     
    ));
  }


  static function matchPageAccept($pid, $mid, $hour){
    return self::matchPage($pid, array(
      'qwik'   => 'accept',
      'id'     => $mid,
      'hour'   => $hour
    ));
  }


  static function matchPageFeedback($pid, $mid, $parity, $rep='0'){
    return self::matchPage($pid, array(
      'qwik'   => 'feedback',
      'id'     => $mid,
      'parity' => $parity,
      'rep'    => $rep
    ));
  }


  static function matchPageCancel($pid, $mid){
    return self::matchPage($pid, array(
      'qwik'   => 'cancel',
      'id'     => $mid,
    ));
  }
    
    
  static function indexPage($post){
    $_GET = array();
    $_POST = $post;
    $page = new IndexPage();
    return $page->processRequest();
  }


  static function matchPage($pid, $post){
    $_GET = array();
    $_SESSION['pid'] = $pid;
    $_POST=$post;
    $page = new MatchPage();
    return $page->processRequest();
  }


  static function favoritePage($pid, $post){
    $_GET = array();
    $_SESSION['pid'] = $pid;
    $_POST=$post;
    $page = new FavoritePage();
    return $page->processRequest();
  }
  

  static function friendPage($pid, $post){
    $_GET = array();
    $_SESSION['pid'] = $pid;
    $_POST=$post;
    $page = new FriendPage();
    return $page->processRequest();
  }


  static function facilityPage($pid, $post){
    $_GET = array();
    $_SESSION['pid'] = $pid;
    $_POST=$post;
    $page = new FacilityPage();
    return $page->processRequest();
  }


  static function bookingPage($pid, $post){
    $_GET = array();
    $_SESSION['pid'] = $pid;
    $_POST=$post;
    $page = new BookingPage();
    // $result = $page->processRequest();
    // return isset($result) ? $result : $page->make();
    return $page->make();
  }



  public function __construct(){ }

}


?>
