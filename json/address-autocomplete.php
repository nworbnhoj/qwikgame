<?php
    require_once 'LocatePage.php';

    $param = array();
    $param['input'] = $_GET['input'];
    $param['key'] = LocatePage::$geoplace->key();
    $url = LocatePage::$geoplace->url("json");
    $query = http_build_query($param);
    $reply = file_get_contents("$url?$query");
    $tidy = tidy_parse_string($reply, self::TIDY_CONFIG, 'utf8');
    $tidy->cleanRepair();
    echo tidy_get_output($tidy);
?>
