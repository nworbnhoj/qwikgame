<?php
header('Content-Type: application/json');

require_once 'class/Qwik.php';
require_once 'class/Options.php';

$options = new Options(NULL, Options::VALUE_TEMPLATE);
$options->values($options->regions());
$opts = $options->make();

$json = json_encode($opts);

if(!$json){
    $json_error = json_last_error();
    Qwik::logMsg("(region.options.php) json error: $json_error");
}
 
echo $json;



?>

