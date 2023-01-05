<?php

require_once 'Page.php';

class BookingPage extends Page {


    public function __construct($templateName='booking'){
        parent::__construct(NULL, $templateName);
    }

}

?>
