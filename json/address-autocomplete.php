<?php
    $PLACE_URL = "https://maps.googleapis.com/maps/api/place/autocomplete/json";
    $PLACE_API_KEY = "AIzaSyDne6EhcdFtiEiUT-batwVilT9YFUAbYdM";

    $param = array();
    $param['input'] = $_GET['input'];
    $param['key'] = $PLACE_API_KEY;
    $query = http_build_query($param);
    $url = "$PLACE_URL?$query";
    $reply = file_get_contents("url");
    $tidy = tidy_parse_string($reply, self::TIDY_CONFIG, 'utf8');
    $tidy->cleanRepair();
    echo tidy_get_output($tidy);
?>
