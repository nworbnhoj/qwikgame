<?php


require_once 'class/MatchPage.php';


class Get {

    
    
  static function matchPageKeen($pid, $token, $game, $vid, $today, $tomorrow, $invite){
      return self::matchPage(array(
        'qwik'     => 'keen',
        'pid'      => $pid,
        'token'    => $token,
        'game'     => $game,
        'vid'      => $vid,
        'today'    => $today,
        'tomorrow' => $tomorrow,
        'invite'   => $invite     
      ));
    }


  static function matchPageAccept($pid, $token, $mid, $hour){
      return self::matchPage(array(
        'qwik'   => 'accept',
        'pid'    => $pid,
        'token'  => $token,
        'id'     => $mid,
        'hour'   => $hour
      ));
    }


  static function matchPageFeedback($pid, $token, $mid, $parity, $rep='0'){
      return self::matchPage(array(
        'qwik'   => 'feedback',
        'pid'    => $pid,
        'token'  => $token,
        'id'     => $mid,
        'parity' => $parity,
        'rep'    => $rep
      ));
    }


  static function matchPage($get){
      $_GET=$get;
      $page = new MatchPage();
      return $page->processRequest();
    }




  public function __construct(){ }

}


?>
