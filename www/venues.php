<?php
require_once 'up.php';
require_once UP.PATH_CLASS.'VenuesPage.php';

$page = new VenuesPage('venues');
$page->serve();
?>
