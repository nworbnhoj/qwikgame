<?php
header('Content-Type: application/json');

require_once 'class/Qwik.php';
require_once 'class/Defend.php';
require_once 'class/MatchListing.php';

$defend = new Defend();
$get = $defend->get();

$matchListing = new MatchListing($get['html'], 'accepted');
$listing = $matchListing->make();
$json = json_encode($listing);

$json_error = json_last_error();
if($json_error !== JSON_ERROR_NONE){
    Qwik::logMsg("(accepted.match.listing.php) json error: $json_error\n html = $html");
}
 
echo $json;



?>

