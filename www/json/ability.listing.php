<?php
header('Content-Type: application/json');

require_once 'up.php';
require_once PATH_CLASS.'Qwik.php';
require_once PATH_CLASS.'Defend.php';
require_once PATH_CLASS.'AbilityListing';

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

