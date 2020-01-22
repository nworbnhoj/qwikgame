<?php
header('Content-Type: application/json');

require_once 'class/Qwik.php';
require_once 'class/SvidOptions.php';


$svidOptions = new SvidOptions();
$options = $svidOptions->make();

$json = json_encode($options);

$json_error = json_last_error();
if($json_error !== JSON_ERROR_NONE){
    Qwik::logMsg("(svid.options.php) json error: $json_error\n html = $html");
}
 
echo $json;



?>

