<?php
require_once 'up.php';
require_once PATH_CLASS.'UploadPage.php';

$page = new UploadPage();
$page->serve();
?>
