<?php
header('Content-Type: application/json');

require_once 'class/Qwik.php';
require_once 'class/Defend.php';
require_once 'class/SvidOptions.php';

$defend = new Defend();
$get = $defend->get();
$game = $get['game'];
$country = ''; // $get['country'];

$svidOptions = new SvidOptions($game, $country);
$options = $svidOptions->make();

$json = json_encode($options);

if(!$json){
    $json_error = json_last_error();
    Qwik::logMsg("(svid.options.php) json error: $json_error\n game=$game country=$country");
}
 
echo $json;



?>

