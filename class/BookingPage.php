<?php

require_once 'Page.php';
require_once 'Venue.php';
require_once 'FacilityMatchList.php';

class BookingPage extends Page {

    protected static function loadUser($uid){
        return new Manager($uid);
    }

    private $venue;

    public function __construct($templateName='booking'){
        parent::__construct(NULL, $templateName);

        $manager = $this->manager();
        if (is_null($manager)
        || !$manager->ok()){
            $this->logout();
            return;
        }

        $vid = $this->req('vid');
        if(isset($vid)){
            if (Venue::exists($vid)){
                try {
                    $this->venue = new Venue($vid);
                } catch (RuntimeException $e){
                    self::alert("{Oops}");
                    self::logThrown($e);
                    unset($vid);
                }
            }
        }
    }


    public function processRequest(){
        $result = parent::processRequest();
        if(!is_null($result)){ return $result; }   // request handled by parent
        
        $manager = $this->manager();
        $qwik = $this->req('qwik');
        $req = $this->req();
        switch ($qwik) {
            case 'book':
                $result = $this->qwikBook($req);
                 break;
            case 'call':
                $result = $this->qwikCall($req);
                 break;
            default:
                $result =  NULL;
        }

        $this->venue->concludeMatches();
        $this->venue->save();
        return $result;
    }


    public function variables(){
        $vars = parent::variables();

        $venue = $this->venue;
        if (isset($venue)){
            $vars['vid'] = $venue->id();
            $vars['venue'] = $venue->name();
        } else {
            $vars['vid'] = '';
            $vars['venue'] = '';
        }

        return $vars;
    }


    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();

        $tentativeList = new FacilityMatchList($html, 'tentative', 'tentative.match');
        $vars['tentativeMatches'] = $tentativeList->make();

        $confirmedList = new FacilityMatchList($html, 'confirmed', 'confirmed.match');
        $vars['confirmedMatches'] = $confirmedList->make();

        $cancelledList = new FacilityMatchList($html, 'cancelled', 'cancelled.match');
        $vars['cancelledMatches'] = $cancelledList->make();
    
        return parent::make($vars); 
    }



///// QWIK SWITCH ///////////////////////////////////////////////////////////


    function qwikBook($request){
        $mid = $request['id'];
        if(isset($mid)){
            $match = $venue->match($mid);
            if (isset($match)){
                $player = $match->player();
                $player->matchMsg($mid, "Facility booking confirmed");
            }
        }
    }


    function qwikCall($request){
        $mid = $request['id'];
        if(isset($mid)){
            $match = $venue->match($mid);
            if (isset($match)){
                $player = $match->player();
                $player->matchMsg($mid, "Facility booking problem. Please contact Venue");
                self::message("{call_alert}");
            }
        }
    }


}

?>
