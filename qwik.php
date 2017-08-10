<?php 



const REVERT_CHAR = '⟲';gitk



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



function subID($id){
    return (string)$id;
    return substr("$id",0, 10);
}


////////////////////// TIME //////////////////////////////

// handy bitfield constants
$Hrs24 = 33554431;
$Hrs6amto8pm = 16777215;


function hour2bit($hour){
    if ($hour < 0 || $hour >= 24) {
        return 0;
    }
    return 2 ** $hour;
}

function bit2hour(){
    $mask = 1;
    for ($hour = 0; $hour < 24; $hour++){
        if ($bits & $mask){
            return $hour;
        }
        $mask = $mask * 2;
    }    
}

/*******************************************************************************

*******************************************************************************/
function hours2bits($request, $day){
    $bitfield = 0;
    for ($hour = 0; $hour < 24; $hour++){
        $name = "$day$hour";
        if(isset($request[$name])){
            $bitfield += $request[$name];
        }
    } 
    return $bitfield;
}


// Accepts a bitfield representing the 24hrs in a day and returns and array of hours
function hours($bits){
    $hours = array();
    $mask = 1;
    for ($hour = 0; $hour < 24; $hour++){
        if ($bits & $mask){
            $hours[] = $hour ;
        }
        $mask = $mask * 2;
    }    
    return $hours;
}


function addHoursXML($element, $request, $day){
    $hourBits = hours2bits($request, $day);
    if ($hourBits > 0){
        $element->addChild($day, hours2bits($request,$day));
        return True;
    }
    return FALSE;
}


function daySpan($bits, $day=''){
    global $clock24hr;
    $hours = hours($bits);
    if (count($hours) > 0){
        $dayX = substr($day, 0, 3);
        $dayP = $clock24hr ? 0 : 12;

        if (count($hours) == 24){
            return "<span class='lolite'><b>$dayX</b></span>";
        } else {
            $str =  $clock24hr ? $dayX : '';
            $last = null;
            foreach($hours as $hr){
                $pm = $hr > 12;
                $consecutive = $hr == ($last + 1);
                $str .= $consecutive ? "&middot" : clock($last) . ' ';
    
                if ($pm && !$clock24hr) {
                    $str .= "<b>$dayX</b>";
                    $dayX = '';
                }

                $str .= $consecutive ? '' : " " . clock($hr);
                $last = $hr;
            }
            $str .= $consecutive ? clock($last) : '';
            return "<span class='lolite'>$str</span>";
        }
    }
    return "";
}


function parityStr($parity){
//echo "<br>PARITYSTR $parity<br>";
    if(!is_numeric("$parity")){
        return '';
    }

    $pf = floatval($parity);
    if($pf <= -2){
        return "{much_weaker}";
    } elseif($pf <= -1){
        return "{weaker}";
    } elseif($pf < 1){
        return "{well_matched}";
    } elseif($pf < 2){
        return "{stronger}";
    } else {
        return "{much_stronger}";
    }
}
                                                                                           
?>
