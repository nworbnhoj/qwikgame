<?php
header('Content-Type: application/json');

require_once 'up.php';
require_once PATH_CLASS.'Qwik.php';
require_once PATH_CLASS.'Defend.php';
require_once PATH_CLASS.'Options.php';

$defend = new Defend();
$get = $defend->get();
$game = $get['game'];
$country = ''; // $get['country'];

$options = new Options(Qwik::svids($game, $country), Options::DATALIST_TEMPLATE);
$opts = $options->make();

$json = json_encode($opts);

if(!$json){
    $json_error = json_last_error();
    Qwik::logMsg("(svid.options.php) json error: $json_error\n game=$game country=$country");
}
 
echo $json;



?>

