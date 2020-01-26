<?php
header('Content-Type: application/json');

require_once 'class/Qwik.php';
require_once 'class/Defend.php';
require_once 'class/FriendListing.php';


$defend = new Defend();
$get = $defend->get();
$html = $get['html'];

$friendListing = new FriendListing($html);
$listing = $friendListing->make();
$json = json_encode($listing);

if(!$json){
    $json_error = json_last_error();
    Qwik::logMsg("(friend.listing.php) json error: $json_error\n html = $html");
}
 
echo $json;



?>

