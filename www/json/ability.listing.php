<?php
header('Content-Type: application/json');

require_once 'class/Qwik.php';
require_once 'class/Defend.php';
require_once 'class/AbilityListing.php';

$defend = new Defend();
$get = $defend->get();
$html = $get['html'];

$abilityListing = new AbilityListing($html);
$listing = $abilityListing->make();
$json = json_encode($listing);

if(!$json){
    $json_error = json_last_error();
    Qwik::logMsg("(ability.listing.php) json error: $json_error\n html = $html");
}
 
echo $json;



?>

