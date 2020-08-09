<?php
header('Content-Type: application/json');

require_once 'up.php';
require_once PATH_CLASS.'Qwik.php';
require_once PATH_CLASS.'Options.php';

$defend = new Defend();
$get = $defend->get();
$game = $get['game'];

$options = new Options(NULL, Options::KEYVALUE_TEMPLATE);

$favoriteVenues = $options->favoriteVenues($game);
$matchVenues = $options->matchVenues($game);
$otherVenues = array_diff_assoc($matchVenues, $favoriteVenues);

$options->values($otherVenues);
$opts = $options->make();

$json = json_encode($opts);

if(!$json){
    $json_error = json_last_error();
    Qwik::logMsg("(venue-other.options.php) json error: $json_error");
}
 
echo $json;
?>

