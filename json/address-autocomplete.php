<?php
    require_once 'Locate.php';

    $defend = new Defend();
    $get = $defend->get();
    echo Locate::geoGuess($get["input"]);
?>
