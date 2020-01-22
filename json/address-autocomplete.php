<?php
    require_once 'class/Locate.php';

    $defend = new Defend();
    $get = $defend->get();
    echo Locate::geoGuess($get["input"]);
?>
