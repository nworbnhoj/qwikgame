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
        if (!$this->venue->ok()){
            return;
        }

        $venue = $this->venue;
        $req = $this->req();
        if($this->player() !== null
        && $req['name'] !== null
        && $req['address'] !== null
        && $req['country'] !== null){
            $address = LocatePage::parseAddress($req['address'].', '.$req['country']);

            $save = $venue->updateAtt('name',     $req['name']);
            $save = $venue->updateAtt('locality', $address['locality'])  || $save;
            $save = $venue->updateAtt('admin1',   $address['admin1'])    || $save;
            $save = $venue->updateAtt('country',  $address['country'])   || $save;
            if($save){
                $venue->updateID();
            }
            $save = $venue->updateAtt('phone',     $req['phone'])         || $save;
            $save = $venue->updateAtt('url',       $req['url'])           || $save;
            $save = $venue->updateAtt('tz',        $req['tz'])            || $save;
            $save = $venue->updateAtt('note',      $req['note'])          || $save;
            $save = $venue->updateAtt('lat',       $address['lat'])       || $save;
            $save = $venue->updateAtt('lng',       $address['lng'])       || $save;
            $save = $venue->updateAtt('placeid',   $address['placeid'])   || $save;
            $save = $venue->updateAtt('address',   $address['formatted']) || $save;
            if($save){
                $venue->save();
            }
        }

        $venue->concludeReverts();
    }



    public function variables(){
        $game = $this->req('game');
        $venueName = $this->venue->name();
        $venueUrl = $this->venue->url();
        $venueCountry = $this->venue->country();
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
        $vars['venueAddress']  = $this->venue->address();
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
        $vars['games']         = $venueGames;
        
        return $vars;
    }

}

?>
