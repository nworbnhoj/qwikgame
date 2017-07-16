<?php
require 'Page.php';

class VenuesPage extends Page {


	public function __construct(){
	    Page::__construct();
	}


    public function serve($template='venues'){
        Page::Serve($template);
    }
	
	
	public function variables(){
        $variables = Page::variables();
	
        $game = $this->req('game');

        $variables['game']          = $games["$game"];
        $variables['COMMENT_ICON']  = COMMENT_ICON;
        $variables['MALE_ICON']     = MALE_ICON;
        $variables['FEMALE_ICON']   = FEMALE_ICON;
        $variables['THUMB_UP_ICON'] = THUMB_UP_ICON;
        $variables['THUMB_DN_ICON'] = THUMB_DN_ICON;
        $variables['LOGOUT_ICON']   = isset($this->player) ? LOGOUT_ICON : '';
        
        return $variables;
    }

}

?>
