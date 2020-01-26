<?php
header('Content-Type: application/json');

require_once 'class/Qwik.php';
require_once 'class/Defend.php';
require_once 'class/MatchListing.php';

$defend = new Defend();
$get = $defend->get();

$matchListing = new MatchListing($get['html'], 'history');
$listing = $matchListing->make();
$json = json_encode($listing);

if(!$json){
    $json_error = json_last_error();
    Qwik::logMsg("(history.match.listing.php) json error: $json_error\n html = $html");
}
 
echo $json;



?>

