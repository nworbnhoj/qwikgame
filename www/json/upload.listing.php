<?php
header('Content-Type: application/json');

require_once 'up.php';
require_once PATH_CLASS.'Qwik.php';
require_once PATH_CLASS.'Defend.php';
require_once PATH_CLASS.'UploadList.php';

$defend = new Defend();
$get = $defend->get();
$html = html_entity_decode($get['html']);
$html = mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");

$uploadList = new UploadList($html);
$listing = $uploadList->make();
$json = json_encode($listing);

if(!$json){
    $json_error = json_last_error();
    Qwik::logMsg("(upload.listing.php) json error: $json_error\n html = $html");
}
 
echo $json;



?>

