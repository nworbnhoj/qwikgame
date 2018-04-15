<?php

require_once 'Page.php';
require_once 'LocatePage.php';

class VenuePage extends Page {

    private $venue;

    public function __construct($template='venue'){
        parent::__construct($template);
        $vid = $this->req('vid');
        $this->venue = new Venue($vid);
    }


    public function serve(){
        if (!$this->venue->ok()){
            header("Location: ".self::QWIK_URL);
            return;
        }
        parent::serve();
    }


    public function processRequest(){
        $venue = $this->venue;
        if (!$venue->ok()
        || $this->player() == NULL){
            return;
        }

        $req = $this->req();
        $name     = $req['name'];
        $locality = $req['locality'];
        $admin1   = $req['admin1'];
        $country  = $req['country'];
        $placeid  = isset($req['placeid']) ? $req['placeid'] : $this->venue->placeid();
        $details = NULL;
        if(empty($placeid)
            && isset($name)
            && isset($locality)
            && isset($admin1)
            && isset($country)){
            $placeid = LocatePage::getPlace("$name, $locality, $admin1, $country");
            if(!empty($placeid)){
                $details = LocatePage::getDetails($placeid);
            }
        } else {
            $details = LocatePage::getDetails($placeid);
            $req['locality'] = $details['locality'];
            $req['admin1']   = $details['admin1'];
            $req['country']  = $details['country'];
            $req['address']  = $details['formatted'];
            $req['tz']       = $details['tz'];
        }

        if(isset($details)){
            if(empty($req['phone'])){ $req['phone'] = $details['phone'];}
            if(empty($req['url']))  { $req['url']   = $details['url'];  }
            if(empty($req['lat']))  { $req['lat']   = $details['lat'];  }
            if(empty($req['lng']))  { $req['lng']   = $details['lng'];  }
        }

        $keys = array('placeid','address','tz','phone','url','lat','lng');
        $changed = $this->venueAttributes($venue, $req, $keys);
        $keys = array('name','locality','admin1','country');
        if($this->venueAttributes($venue, $req, $keys)){
            $venue->updateID();
        } elseif($changed){
            $venue->save();
        }
        $venue->concludeReverts();
    }


    private function venueAttributes($venue, $vals, $keys){
        $changed = FALSE;
        foreach($keys as $key){
            $changed = $venue->updateAtt($key, $vals[$key]) || $changed;
        }
        return $changed;
    }


    public function variables(){
        $placeid = $this->venue->placeid();
        $game = $this->req('game');
        $venueName = $this->venue->name();
        $venueUrl = $this->venue->url();
        $backLink = "<a href='".self::QWIK_URL;
        $backLink .= "/index.php?venue=$venueName&game=$game' target='_blank'><b>link</b></a>";

        $qwikGames = $this->qwikGames();
        $venueGames = "";
        foreach($this->venue->games() as $gameKey){
            $gameName = $qwikGames[$gameKey];
            $venueGames .= "{$gameName} ";
        }

        $vars = parent::variables();
        
        $vars['vid']           = $this->venue->id();
        $vars['playerCount']   = $this->venue->playerCount();
        $vars['message']       = '';
        $vars['displayHidden'] = '';
        $vars['editHidden']    = 'hidden';
        $vars['venueName']     = $venueName;
        $vars['localityL']     = $this->venue->locality();
        $vars['admin1']        = $this->venue->admin1();
        $vars['country']       = $this->venue->country();
        $vars['venueAddress']  = $this->venue->address();
        $vars['venuePhone']    = $this->venue->phone();
        $vars['venueURL']      = $this->venue->url();
        $vars['venueTZ']       = $this->venue->tz();
        $vars['venueLat']      = $this->venue->lat();
        $vars['venueLng']      = $this->venue->lng();
        $vars['venueNote']     = $this->venue->note();
        $vars['venueRevertDiv']= $this->venue->revertDiv();
        $vars['backLink']      = $backLink;
        $vars['venueUrlLink']  = "<a href='$venueUrl'>{homepage}</a>";
        $vars['games']         = $venueGames;
        $vars['placeid']       = $placeid;
        $vars['disabled']      = empty($placeid) ? '' : 'disabled';
        
        return $vars;
    }

}

?>
