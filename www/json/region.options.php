<?php
header('Content-Type: application/json');

require_once 'up.php';
require_once PATH_CLASS.'Qwik.php';
require_once PATH_CLASS.'Options.php';

$defend = new Defend();
$get = $defend->get();

$options = new Options(NULL, Options::KEYVALUE_TEMPLATE);
$options->values($options->regions());
$opts = $options->make();

$json = json_encode($opts);

if(!$json){
    $json_error = json_last_error();
    Qwik::logMsg("(region.options.php) json error: $json_error");
}
 
echo $json;
?>

