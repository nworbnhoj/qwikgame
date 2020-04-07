<?php

require_once 'Page.php';
require_once 'Locate.php';

class VenuePage extends Page {

    private $venue;

    public function __construct($templateName='venue'){
        parent::__construct(NULL, $templateName);

        $vid = $this->req('vid');
        $this->venue = new Venue($vid);
    }


    public function serve(){
        if (!$this->venue->ok()){
            header("Location: ".QWIK_URL);
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
        if(!isset($req['name'])
        || !isset($req['locality'])
        || !isset($req['admin1'])
        || !isset($req['country'])){
            return;
        }

        $details = NULL;
        $placeid = $req['placeid'];
        if(empty($placeid)){
            $name     = $req['name'];
            $num      = $req['str-num'];
            $route    = $req['route'];
            $locality = $req['locality'];
            $admin1   = $req['admin1'];
            $country  = $req['country'];
            $address = "$num $route, $locality, $admin1 $country";
            $req['address'] = $address;
            $placeid = Locate::getPlace($address, $country);
            if(empty($placeid)){
                $req['tz'] = Locate::guessTimezone($locality, $admin1, $country);
            } else {
                $details = Locate::getDetails($placeid);
            }
        } else {
            $details = Locate::getDetails($placeid);
            $req['str-num']  = $details['street_number'];
            $req['route']    = $details['route'];
            $req['locality'] = $details['locality'];
            $req['admin1']   = $details['admin1'];
            $req['country']  = $details['country_iso'];
            $req['address']  = $details['formatted'];
            $req['tz']       = $details['tz'];
        }

        if(isset($details)){
            if(empty($req['phone'])){ $req['phone'] = $details['phone'];}
            if(empty($req['url']))  { $req['url']   = $details['url'];  }
            if(empty($req['lat']))  { $req['lat']   = $details['lat'];  }
            if(empty($req['lng']))  { $req['lng']   = $details['lng'];  }
        }

        $keys = array('placeid','address','str-num','route','tz','phone','url','lat','lng','note');
        $changed = $this->venueAttributes($venue, $req, $keys);
        $keys = array('name','locality','admin1','country');
        try {
            if($this->venueAttributes($venue, $req, $keys)){
                $venue->updateID();
            } elseif($changed){
                $venue->save(TRUE);
            }
            $venue->concludeReverts();
        } catch (RuntimeException $e){
            $this->alert("{Oops}");
            throw $e;
        }

    }


    private function venueAttributes($venue, $vals, $keys){
        $changed = FALSE;
        foreach($keys as $key){
            if(isset($vals[$key])){
                $changed = $venue->updateAtt($key, $vals[$key]) || $changed;
            }
        }
        return $changed;
    }


    public function variables(){
        $game = $this->req('game');
        $venueName = $this->venue->name();
        $venueUrl = $this->venue->url();
        $venueLink = empty($venueUrl) ? '' : "<a href='$venueUrl' target='_blank'>{homepage}</a>";
        $backLink = "<a href='".QWIK_URL."index.php?venue=$venueName&game=$game' target='_blank'><b>link</b></a>";

        $qwikGames = $this->qwikGames();
        $venueGames = "";
        foreach($this->venue->games() as $gameKey){
            $gameName = $qwikGames[$gameKey];
            $venueGames .= "{$gameName} ";
        }

        $vars = parent::variables();
        
        $vars['vid']           = $this->venue->id();
        $vars['playerCount']   = $this->venue->playerCount();
        $vars['venueName']     = $venueName;
        $vars['venueStrNum']   = $this->venue->strNum();
        $vars['venueRoute']    = $this->venue->route();
        $vars['venueLocality'] = $this->venue->locality();
        $vars['venueAdmin1']   = $this->venue->admin1();
        $vars['venueCountry']  = $this->venue->country();
        $vars['venueAddress']  = $this->venue->address();
        $vars['venuePhone']    = $this->venue->phone();
        $vars['venueURL']      = $this->venue->url();
        $vars['venueLat']      = $this->venue->lat();
        $vars['venueLng']      = $this->venue->lng();
        $vars['venueNote']     = $this->venue->note();
        $vars['venueRevertSet']= $this->venue->revertSet();
        $vars['backLink']      = $backLink;
        $vars['venueUrlLink']  = $venueLink;
        $vars['games']         = $venueGames;
        $vars['placeid']       = $this->venue->placeid();
        return $vars;
    }

}

?>
