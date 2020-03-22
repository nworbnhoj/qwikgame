<?php
header('Content-Type: application/json');

require_once 'up.php';
require_once UP.PATH_CLASS.'Qwik.php';
require_once UP.PATH_CLASS.'Defend.php';
require_once UP.PATH_CLASS.'UploadListing.php';

$defend = new Defend();
$get = $defend->get();
$html = $get['html'];

$uploadListing = new UploadListing($html);
$listing = $uploadListing->make();
$json = json_encode($listing);

if(!$json){
    $json_error = json_last_error();
    Qwik::logMsg("(upload.listing.php) json error: $json_error\n html = $html");
}
 
echo $json;



?>

