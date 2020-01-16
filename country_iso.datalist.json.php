<?php
header('Content-Type: application/json');

require_once 'class/Qwik.php';

$options = '';
foreach(Qwik::countries() as $val => $txt){
    $options .= "\t<option value='$val'>\n";
}


$json = json_encode($options);

$json_error = json_last_error();
if($json_error !== JSON_ERROR_NONE){
    Qwik::logMsg("(country_iso.datalist.json.php) json error: $json_error\n html = $html");
}
 
echo $json;



?>

