<?php
require_once 'up.php';
require_once UP.PATH_CLASS.'Locate.php';

    $defend = new Defend();
    $get = $defend->get();
    echo Locate::geoGuess($get["input"]);
?>
