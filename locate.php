<?php
require_once 'LocatePage.php';

$_GET['game'] = 'squash';
$_GET['venue'] = 'Qwikgame Test2';

$page = new LocatePage();
$page->serve();
?>

