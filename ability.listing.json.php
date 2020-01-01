<?php
header('Content-Type: application/json');

require_once 'class/Qwik.php';
require_once 'class/AbilityListing.php';


$abilityListing = new AbilityListing();
$listing = $abilityListing->make();
$json = json_encode($listing);

$json_error = json_last_error();
if($json_error !== JSON_ERROR_NONE){
    Qwik::logMsg("(ability.listing.json.php) json error: $json_error\n html = $html");
}
 
echo $json;



?>

