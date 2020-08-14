<?php

require 'Page.php';

class InfoPage extends Page {

    public function __construct($templateName='info'){
        parent::__construct(NULL, $templateName);
    }

}

?>
