<?php

require_once 'Page.php';
require_once 'Venue.php';
require_once 'MatchList.php';
require_once 'Locate.php';


class FacilityPage extends Page {

    private $venue;
    private $manager;

    public function __construct($templateName='match'){
        parent::__construct(NULL, $templateName);

        $manager = $this->manager();
        if (is_null($manager)
        || !$manager->ok()){
            $this->logout();
            return;
        }
        $this->manager = $manager;

        $vid = $this->req('vid');
        if(isset($vid)){
            $this->venue = new Venue($vid);
        }

        //sanity check
        // $venue = $this->venue;
        // if(isset($venue)){
        //     $venueManager = $venue->manager();
        //     if (isset($venueManager) && $venueManager->id() != $manager->id()){
        //         self::alert("{Oops}");
        //         $msg = "FacilityPage vid mismatch:";
        //         $msg .= " mid=".$manager->id();
        //         $msg .= " m.vid=".$this->venue->id();
        //         $msg .= " r.vid=".$vid;
        //         self::logMsg($msg);
        //         // $this->logout();
        //         // return;
        //     }       
        // }
    }    


    protected function loadUser($uid){
        return new Manager($uid);
    }


    public function processRequest(){
        $result = parent::processRequest();
        if(!is_null($result)){ return $result; }   // request handled by parent
        

        $qwik = $this->req('qwik');
        $req = $this->req();
        switch ($qwik) {
            case "register":
                if(!isset($req['email'])){
                    $logReq = print_r($req, true);
                    self::logMsg("failed to register manager: $logReq");
                    break;
                }
                $vid = $this->venue->id();
                $manager = $this->manager;
                $manager->email($req['email']);       
                $manager->setVenue($vid);
                $manager->save();
                if(!isset($req['game'])
                || !isset($req['vid'])){
                    break;
                }
                $ddd = array('Mon', 'Tue','Wed', 'Thu', 'Fri');
                foreach($ddd as $d){
                    $req[$d] = Hours::HRS_9AM_to_5PM;
                }                
                $this->venue->setManager($manager->id());

                // intentional flow thru to facility
            case "facility":
                $result = $this->qwikFacility($req, $this->venue);
                break;
            default:
                $result =  NULL;
        }

        $this->venue->save(TRUE);
        return $result;
    }


    public function variables(){
        $vars = parent::variables();
        $vars['vid'] = $this->venue->id();
        $vars['gameOptions']   = $this->gameOptions($this->game, "\t\t");
        return $vars;
    }


    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();

        $facilityList = new FacilityList($html);
        $vars['availability'] = $facilityList->make();
	
        return parent::make($vars); 
    }



///// QWIK SWITCH ///////////////////////////////////////////////////////////


    function qwikFacility($req, $venue){
        if(!isset($venue) || !isset($req['game'])){ return NULL; }
        $days = array();
        $ddd = array('Sun', 'Mon', 'Tue','Wed', 'Thu', 'Fri', 'Sat');
        foreach($ddd as $d){
            if (isset($req[$d])) {
                $days[$d] = $req[$d];
            }
        }
        $now = $venue->dateTime('now');
        if (isset($req['today'])) {
            $days[$now->format('Y-m-d')] = $req['today'];
        }
        if (isset($req['tomorrow'])) {
            $tom = $now::add(new DateInterval("P1D"));
            $days[$tom->format('Y-m-d')] = $req['tomorrow'];
        }
        $newID = $venue->facilitySet($req['game'], $days);
        return $newID;
    }

}


?>