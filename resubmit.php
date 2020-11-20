<?php

/******************************************************************************
 * Resubmits delayed requests that have been captured as tasks in PATH_DELAYED  
 *
 * Intended to be run frequently as a cron job
 *     * * * * * cd /usr/share/nginx/qwikgame.org/www/; php -q resubmit.php
 *****************************************************************************/

require_once 'up.php';
require_once PATH_CLASS.'Qwik.php';
require_once PATH_CLASS.'MatchPage.php';
require_once PATH_CLASS.'FavoritePage.php';
require_once PATH_CLASS.'FriendPage.php';
require_once PATH_CLASS.'AccountPage.php';
require_once PATH_CLASS.'UploadPage.php';

$end = time() + 55;                                        // end in 55 seconds

while( time() < $end ){
  $wake = time() + 10;	
  $delayed = Qwik::fileList(PATH_DELAYED);             // list of delayed tasks
  foreach($delayed as $id){
    if(!is_file(PATH_DELAYED.$id)){ continue; }
    
    $json = file_get_contents(PATH_DELAYED.$id);
    $task = json_decode($json, TRUE);
    $due = (int)$task['due'];
    $time = time();
    if ( $time >= $due ){                   // task is overdue for resubmission
      resubmit($task, $id);
    } elseif( $due < $wake ){                     // task due before next check
      $wake = $due;                               //   bring forward next check
    }
  }
  time_sleep_until($wake);                           // check again in < 10 sec
}


/******************************************************************************
 * Resubmits a delayed task for processing
 * @param Array $task details with mandatory keys: target, post, get & session
 * @return TRUE on success (false otherwise)
 *****************************************************************************/
function resubmit($task, $id){
  if (!isset($task['target'], $task['post'], $task['get'], $task['session'])){
    return FALSE; 
  }
  
  $target   = $task['target'];
  $_POST    = $task['post'];
  $_GET     = $task['get'];
  $_SESSION = $task['session'];
  switch ($target){
    case '/match.php':     $page = new MatchPage();     break;
    case '/favorite.php':  $page = new FavoritePage();  break;
    case '/friend.php':    $page = new FriendPage();    break;
    case '/account.php':   $page = new AccountPage();   break;
    case '/upload.php':    $page = new UploadPage();    break;
    default:
      $json = json_encode($task);
      Qwik::logMsg("failed to resubmit $id $due\n$json");
      $page = null;
  }
  
  if (isset($page)){
    $ignore = $page->serve();                      // complete the task
    unlink(PATH_DELAYED.$id);                      // remove the completed task
    return TRUE;
  }
  return FALSE;
}


?>
