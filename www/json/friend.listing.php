<?php
header('Content-Type: application/json');

require_once 'up.php';
require_once PATH_CLASS.'Qwik.php';
require_once PATH_CLASS.'Defend.php';
require_once PATH_CLASS.'FriendList.php';


$defend = new Defend();
$get = $defend->get();
$html = html_entity_decode($get['html']);
$html = mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");

$friendList = new FriendList($html);
$listing = $friendList->make();
$json = json_encode($listing);

if(!$json){
    $json_error = json_last_error();
    Qwik::logMsg("(friend.listing.php) json error: $json_error\n html = $html");
}
 
echo $json;



?>

