<?php
header('Content-Type: application/json');

require_once 'class/Qwik.php';
require_once 'class/UploadListing.php';


$uploadListing = new UploadListing($html);
$listing = $uploadListing->make();
$json = json_encode($listing);

$json_error = json_last_error();
if($json_error !== JSON_ERROR_NONE){
    Qwik::logMsg("(upload.listing.php) json error: $json_error\n html = $html");
}
 
echo $json;



?>

