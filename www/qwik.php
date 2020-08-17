<?php 

const REVERT_CHAR = '⟲';

$geo;
$star = '★';
$stars = array("","$star","$star$star","$star$star$star","$star$star$star$star","$star$star$star$star$star");
$clock24hr = FALSE;
$tick = 'fa-check-circle';
$cross = 'fa-times-circle';
$help = 'fa-question-circle';
$home = 'fa-home';
$reload = 'fa-refresh';
$revert = '⟲';
$back = '⤺';
$bug = '☹';
$logout = 'fa-power-off';

$status = array(
    'keen'       => 1,
    'invitation' => 2,
    'accepted'   => 3,
    'confirmed'  => 4,
    'feedback'   => 5,
    'history'    => 6,
    'cancelled'  => 10
);



// https://stackoverflow.com/questions/5647461/how-do-i-send-a-post-request-with-php
function post($url, $data){
    // use key 'http' even if you send the request to https://...
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */ }
    
    var_dump($result);
}


function addHoursXML($element, $request, $day){
    $hourBits = hours2bits($request, $day);
    if ($hourBits > 0){
        $element->addChild($day, htmlspecialchars(hours2bits($request,$day)));
        return True;
    }
    return FALSE;
}


                                                                                           
?>
