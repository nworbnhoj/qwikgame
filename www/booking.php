<?php
require_once 'up.php';
require_once PATH_CLASS.'BookingPage.php';

$page = new BookingPage();
$page->serve();
?>

