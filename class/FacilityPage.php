<?php

require_once 'Page.php';
require_once 'Venue.php';
require_once 'FacilityList.php';
require_once 'Locate.php';


class FacilityPage extends Page {

    private $venue;

    public function __construct($templateName='facility'){
        parent::__construct(NULL, $templateName);

        $req = $this->req();

        $manager = $this->manager();
        if (is_null($manager)
        || !$manager->ok()){
            self::logMsg("FacilityPage missing manager ".json_encode($req));
            $this->logout();
            return;
        }

        $this->venue = $manager->venue();
        if (is_null($this->venue)) {
            $qwik = $req['qwik'];
            $vid = $req['vid'];
            if(isset($qwik, $vid) && strcmp($qwik, 'register') == 0){
                return;
            }
            self::logMsg("FacilityPage missing venue ".json_encode($req));
            $this->logout();
            return;
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
                $email = $req['email'];
                if(!isset($email)){
                    $logReq = print_r($req, true);
                    self::logMsg("failed to register manager: $logReq");
                    break;
                }
                $vid = $req['vid'];
                $manager = $this->manager();
                $manager->email($email);       
                $manager->setVenue($vid);
                $manager->save();
                if(!isset($req['game'])
                || !isset($req['vid'])){
                    self::logMsg("missing parameters: game vid");
                    break;
                }
                $ddd = array('Mon', 'Tue','Wed', 'Thu', 'Fri');
                foreach($ddd as $d){
                    $req[$d] = Hours::HRS_9AM_to_5PM;
                }
                $this->venue = new Venue($vid, TRUE);
                $this->venue->setManager($manager->id());
                // intentional flow thru to facility
            case "facility":
                $result = $this->qwikFacility($req, $this->venue);
                break;
            case 'delete':
                $result = $this->qwikDelete($this->venue, $req);
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
        $vars['gameOptions']   = $this->gameOptions(NULL, "\t\t");
        $vars['hourRows'] = self::hourRows(Page::WEEKDAYS);
        $vars['venue'] = $this->venue->name();
        return $vars;
    }


    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();

        $facilityList = new FacilityList($html, 'availability');
        $vars['availability'] = $facilityList->make();
	
        return parent::make($vars); 
    }



///// QWIK SWITCH ///////////////////////////////////////////////////////////


    function qwikFacility($req, $venue){
        $game = $req['game'];
        if(!(isset($venue) && isset($game))) {
            return NULL;
        }

        if(isset($req['id'])){
            $venue->deleteData($req['id']);
        }

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
            $tom = $now->add(new DateInterval("P1D"));
            $days[$tom->format('Y-m-d')] = $req['tomorrow'];
        }
        $newID = $venue->facilitySet($game, $days);
        return $newID;
    }


    function qwikDelete($venue, $request){
        return $venue->deleteData($request['id']);
    }

}


?>