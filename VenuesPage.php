<?php
require 'Page.php';

class VenuesPage extends Page {


	public function __construct(){
	    Page::__construct();
	}


    public function serve($template='venues'){
        Page::Serve($template);
    }
	
}

?>
