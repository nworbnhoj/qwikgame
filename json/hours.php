<?php

    require_once('../class/Page.php');
    require_once('../class/Venue.php');

    $min = $_GET['min'];
    $max = $_GET['max'];
    $vid = $_GET['venue'];

    try {
        $venue = new Venue($vid, FALSE);
        $tz = empty($venue) ? local : $venue->tz();

        $tds = '';
        $bit = 1;
        for($hr=0; $hr<24; $hr++){
            $hidden = ($hr<$min || $hr>$max) ? 'hidden' : '';
            $table .= "\t\t<td class='toggle' bit='$bit' $hidden>$hr</td>\n";
            $bit = $bit * 2;
        }
    } catch (RuntimeException $e){
        self::logThrown($e);
    }
    return $tds;

?>
