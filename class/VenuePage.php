<?php

require 'Page.php';

class VenuePage extends Page {

    private $venue;

    public function __construct($template='venue'){
        parent::__construct($template);

	    $vid = $this->req('vid');
        $this->venue = new Venue($vid, $this->log());
    }


    public function serve(){
        if (!$this->venue->exists()){
            header("Location: ".QWIK_URL);
            return;
	    }
	    parent::serve();
	}


	public function processRequest(){
        if (!$this->venue->exists()){
            return;
        }

        if($this->player() !== null
	    && $this->req('name') !== null
        && $this->req('address') !== null
        && $this->req('suburb') !== null
        && $this->req('state') !== null
        && $this->req('country') !== null){
	        $venue->update($this->req());
	    }

        $this->venue->concludeReverts();
    }



    public function variables(){
        $game = $this->req('game');
        $venueName = $this->venue->name();
        $venueUrl = $this->venue->url();
        $venueState = (empty($this->venue->state())) ? $this->geolocate('region') : $this->venue->state();
        $venueCountry = $this->venue->country();
        $backLink = "<a href='".QWIK_URL;
        $backlink .= "/index.php?venue=$venueName&game=$game' target='_blank'><b>link</b></a>";

        $vars = parent::variables();
        
        $vars['vid']           = $this->venue->id();
        $vars['playerCount']   = $this->venue->playerCount();
        $vars['message']       = '';
        $vars['displayHidden'] = '';
        $vars['editHidden']    = 'hidden';
        $vars['repostInputs']  = $this->repostInputs($repost, "\t\t\t");
        $vars['venueName']     = $venueName;
        $vars['venueAddress']  = $this->venue->address();
        $vars['venueSuburb']   = $this->venue->suburb();
        $vars['venueState']    = $venueState;
        $vars['venueCountry']  = $venueCountry;
        $vars['countryOptions']= $this->countryOptions($venueCountry, "\t\t\t\t\t");
        $vars['venuePhone']    = $this->venue->phone();
        $vars['venueURL']      = $this->venue->url();
        $vars['venueTZ']       = $this->venue->tz();
        $vars['venueLat']      = $this->venue->lat();
        $vars['venueLng']      = $this->venue->lng();
        $vars['venueNote']     = $this->venue->note();
        $vars['venueRevertDiv']= $this->venue->revertDiv();
        $vars['backLink']      = $backLink;
        $vars['venueUrlLink']  = "<a href='$venueUrl'>{homepage}</a>";
        
	    return $vars;
    }
    


    private function repostInputs($repost, $tabs=''){
    //echo "<br>REPOSTINPUTS<br>";
        $braces = '[]';
        $inputs = '';
        foreach($repost as $key => $val){
            if (is_array($val)){
                foreach($val as $v){
                //    $v = reclaw($v);
                    $inputs .= "$tabs<input type='hidden' name='$key$braces' value='$v'>\n";
                }
            } else {
                //$val = reclaw($val);
                $inputs .= "$tabs<input type='hidden' name='$key' value='$val'>\n";
            }
        }
        return $inputs;
    }



}

?>
