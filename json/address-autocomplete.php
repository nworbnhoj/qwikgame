<?php
    require_once 'Locate.php';

    $param = array();
    $param['input'] = $_GET['input'];
    $param['key'] = Locate::$geoplace->key();
    $url = Locate::$geoplace->url("json");
    $query = http_build_query($param);
    $reply = file_get_contents("$url?$query");
    $tidy = tidy_parse_string($reply, self::TIDY_CONFIG, 'utf8');
    $tidy->cleanRepair();
    echo tidy_get_output($tidy);
?>
