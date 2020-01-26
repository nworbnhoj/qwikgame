<?php
header('Content-Type: application/json');

require_once 'class/Qwik.php';
require_once 'class/Defend.php';
require_once 'class/AbilityListing.php';

$defend = new Defend();
$get = $defend->get();

$abilityListing = new AbilityListing($get['html']);
$listing = $abilityListing->make();
$json = json_encode($listing);

$json_error = json_last_error();
if(!$json_error && $json_error !== JSON_ERROR_NONE){
    Qwik::logMsg("(ability.listing.php) json error: $json_error\n html = $html");
}
 
echo $json;



?>

