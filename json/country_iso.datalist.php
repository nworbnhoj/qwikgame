<?php
header('Content-Type: application/json');

require_once 'class/Qwik.php';

$options = '';
foreach(Qwik::countries() as $val => $txt){
    $options .= "\t<option value='$val'>\n";
}


$json = json_encode($options);

if(!$json){
    $json_error = json_last_error();
    Qwik::logMsg("(country_iso.datalist.php) json error: $json_error");
}
 
echo $json;



?>

