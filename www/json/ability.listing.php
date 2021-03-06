<?php
header('Content-Type: application/json');

require_once 'up.php';
require_once PATH_CLASS.'Qwik.php';
require_once PATH_CLASS.'Defend.php';
require_once PATH_CLASS.'AbilityList.php';

$defend = new Defend();
$get = $defend->get();
$html = html_entity_decode($get['html']);
$html = mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");

$abilityList = new AbilityList($html);
$listing = $abilityList->make();
$json = json_encode($listing);

if(!$json){
    $json_error = json_last_error();
    Qwik::logMsg("(ability.listing.php) json error: $json_error\n html = $html");
}
 
echo $json;



?>

