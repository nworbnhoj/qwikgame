<?php

require_once 'Qwik.php';
require_once 'Hours.php';
require_once 'Page.php';
require_once 'Venue.php';
require_once 'Notify.php';


class Match extends Qwik {

    const CHAT_TEMPLATE = "<p class='chat [class]'>[chat]</p>";
    const CHAT_ME = 'chat-me';
    const CHAT_YU = 'chat-yu';

    private $player;
    private $xml;
    private $rivalElements;    // temp variable for performance

    public function __construct($player, $xml, $status=NULL, $game=NULL, $venue=NULL, $date=NULL, $hours=NULL, $id=NULL){
        parent::__construct();
        $this->player = $player;
        $this->xml = $xml;
        if(isset($status, $game, $venue, $date, $hours)){
            $id = is_null($id) ? self::newID() : $id;
            $this->xml->addAttribute('id', $id);
            $this->xml->addAttribute('status', $status);
            $this->xml->addAttribute('game', $game);
            $this->xml->addAttribute('date', $date);
            $this->xml->addAttribute('hrs', $hours->bits());
            $v = $this->xml->addChild('venue', htmlspecialchars($venue->id()));
            $v->addAttribute('tz', $venue->tz());
        } else { //refresh the VenueID in case it has been renamed
            $vid = (string) $this->xml->venue[0];
            $id = Venue::refreshID($vid);
            if($id !== $vid){
                unset($this->xml->venue[0]);
                $this->xml->addChild('venue', htmlspecialchars($id));
            }
        }
    }


    public function copy($match){
        $this->xml->addAttribute('id', $match->id());
        $this->xml->addAttribute('status', $match->status());
        $this->xml->addAttribute('game', $match->game());
        $this->xml->addAttribute('date', $match->date());
        $this->xml->addAttribute('hrs', $match->hours()->bits());
        $v = $this->xml->addChild('venue', htmlspecialchars($match->vid()));
        $v->addAttribute('tz', $match->tz());
    }


    public function addRival($rid, $parity=NULL, $rep=NULL, $name=NULL){
        $rival = $this->xml->addChild('rival', htmlspecialchars($rid));
        $rival->addAttribute('parity', $parity);
        $rival->addAttribute('rep', $rep);
        $rival->addAttribute('name', $name);
        $this->rivalElements = NULL; //reset
    }


    public function game(){
        return (string) $this->xml['game'];
    }


    public function status($status=NULL){
        if(!is_null($status)){
            $this->xml['status'] = $status;
        }
        return (string) $this->xml['status'];
    }


    public function pid(){
        return (string) $this->player->id();
    }


    public function playerName(){
        return $this->player()->nick();
    }


    public function id($id=NULL){
        if(!is_null($id)){
            $this->xml['id'] = $id;
        }
        return (string) $this->xml['id'];
    }


    public function hours($hours=NULL){
        if(!is_null($hours)){
            $this->xml['hrs'] = $hours->bits();
        }
        return new Hours($this->xml['hrs']);
    }


    public function vid(){
        return (string) $this->xml->venue[0];
    }


    public function venueName(){
        return explode('|', $this->vid())[0];
    }


    private function rivalElements(){
        if(is_null($this->rivalElements)){
            $this->rivalElements = $this->xml->xpath('rival');
        }
        return $this->rivalElements;
    }


    private function rivalElement($index=0){
        $rivalElements = $this->rivalElements();
        return isset($rivalElements[$index])
            ? $rivalElements[$index]
            : NULL;
    }


    public function rid($index=0){
        $rivalElement = $this->rivalElement($index);
        return isset($rivalElement)
            ? (string) $rivalElement
            : NULL ;
    }

    private function rids(){
        $rids = array();
        $rivalCount = $this->rivalCount();
        for($r=0; $r<=$rivalCount; $r++){
            $rids[r] = $this->rid($r);
        }
        return $rids;
    }


    public function rival($index=0){
        try {
            $rid = $this->rid($index);
            return isset($rid) ? new Player($rid) : NULL;
        } catch (RuntimeException $e){
            self::logThrown($e);
            self::logMsg("failed to retrieve Player $rid");
        }
        return NULL;
    }


    private function rivals(){
        $rivals = array();
        $rivalCount = $this->rivalCount();
        for($r=0; $r<=$rivalCount; $r++){
            $rival = $this->rival($r);
            if(isset($rival)){
                $rivals[$rival->id()] = $rival;
            }
        }
        return $rivals;
    }


    public function rivalParity($index=0){
        $element = $this->rivalElement($index);
        if(isset($element)){
            $parityStr = (string) $element['parity'];
            if(is_numeric($parityStr)){
                return floatval($parityStr);
            }
        }
        return NULL;
    }


    public function rivalName($index=0){
        $element = $this->rivalElement($index);
        if(isset($element)){
            return (string) $element['name'];
        }
        return NULL;
    }


    public function rivalRep($index=0){
        $element = $this->rivalElement($index);
        return isset($element) ? (string) $element['rep'] : NULL;
    }


    public function rivalCount(){
        return count($this->rivalElements());
    }


    public function time($date=NULL, $hour=NULL){
        if(!is_null($date) && !is_null($hour)){
            $this->xml->addAttribute('time', "$date $hour:00");
        }
        return $this->xml['time'];
    }


    public function date(){
        return $this->xml['date'];
    }


    public function tz(){
        return (string) $this->xml->venue['tz'];
    }


    public function venue(){
        $vid = $this->vid();
        return = new Venue($vid, FALSE);
    }


    public function invite($rids){
        $game = $this->game();
        foreach($rids as $rid => $email){
            try {
                $rival = new Player($rid);
                if($rival->ok()){
                    $parity = $this->player->parity($rival, $game);
                    $ytirap = -1 * $parity;
                    $hours = $this->hours();
                    $rivalMatch = is_null($email)
                        ? $rival->matchInvite($this, $ytirap)
                        : $rival->matchAdd($this, $ytirap, $hours, $email);
                    if(!is_null($rivalMatch)){
                        $this->addRival($rid, 
                                        $parity,
                                        $rival->repWord(),
                                        $rival->nick()
                        );
                    }
                    $rival->save();
                }
            } catch (RuntimeException $e){
                self::logThrown($e);
                self::logMsg("failed to invitebPlayer $rid");
            }
        }
    }


    public function accept($acceptHour){
        $rival = $this->rival(0);
        if (!$rival){
            return FALSE;
        }

        $mid = $this->id();
        $rivalMatch = $rival->matchID($mid);
        if (!$rivalMatch){
            return FALSE;
        }

        $rivalStatus = $rivalMatch->status();
        switch ($rivalStatus) {
            case 'keen':
                $mid = self::newID();
                $this->id($mid); //make independent from keenMatch
                $this->status('accepted');
                $this->hours($acceptHour);
                $ytirap = -1 * $this->rivalParity();
                $rival->matchAdd($this, $ytirap, $acceptHour);
                break;
            case 'accepted':
                $hour = $acceptHour->first();
                $date = $this->date();
                $this->confirm($date, $hour);
                $rivalMatch->confirm($date, $hour);
                break;
            default:
        }
        $rival->save();
        return $mid;
    }


    public function confirm($date, $hour){
        $this->status('confirmed');
        $this->time($date, $hour);
        $notify = new Notify($this->player);
        $notify->sendConfirm($this->id());
        $venue = $this->venue();
        if (isset(venue)){
            $manager = $venue->manager();
            if(isset($manager)){
                $notify = new Notify($manager);
                $notify->sendBook($this->id());
            }
        }
    }


    private function removeRival($rid){
        $rivalElement = $this->xml->xpath("rival='$rid'");
        self::removeElement($rivalElement[0]);
        $this->rivalElements = NULL; // reset
    }




    public function cancel(){
        $status = $this->status();
        switch ($this->status()) {
            case 'feedback':
            case 'history':
            case 'cancelled':
                return;
        }

        $this->status('cancelled');
        $this->player->save();

        $rivals = $this->rivals();
        $mid = $this->id();
        switch ($status) {
            case 'confirmed':
                foreach($rivals as $rival){    // usually only one rival
                    $notify = new Notify($rival);
                    $notify->sendCancel($this);
                }
                foreach($rivals as $rival){
                    $rival->matchCancel($mid);
                }         
                break;
            case 'keen':
                foreach($rivals as $rival){
                    $rival->matchCancel($mid);
                }                
                $this->player->deleteData($mid);
                break;
            case 'invitation':
                foreach($rivals as $rival){
                    $rival->matchDecline($mid);
                }
        }
    }


    public function decline(){
        $mid = $this->id();
        $rivals = $this->rivals();
        foreach($rivals as $rival){
            $match = $rival->matchID($mid);
            if (isset($match)) {
                switch ($match->status()){
                    case 'accepted':
                        $match->cancel();
                        $rival->save();
                    break;
                    case 'keen':
                        $this->removeRival($rid);
                    break;
                }
                $rival->save();
            }
        }

        $rivalElements = $this->rivalElements();
        foreach($rivalElements as $element){
            self::removeElement($element);
        }
    }


    public function chat($msg=NULL, $class = ''){
      $chatElements = $this->xml->xpath("chat");
      $chatter = isset($chatElements[0]) ? (string) $chatElements[0] : '' ;
      if(!empty($msg)){
        if(isset($chatElements[0])){
          $this->removeElement($chatElements[0]);
        }
        $chat = htmlspecialchars($msg, ENT_HTML5, 'UTF-8');
        $chat = str_replace('[chat]', $chat, self::CHAT_TEMPLATE);
        $chat = str_replace('[class]', $class, $chat);
        $this->xml->addChild('chat', $chatter.$chat);
      }
      return $chatter;
    }


    public function conclude(){
        $tz = $this->tz();
        $now = self::tzDateTime('now', $tz);
        $dateStr = $this->date();
        $hour = $this->hours()->last();
        switch ($this->status()){
            case 'cancelled':
                $hour = min($hour+6, 24);
            case 'keen':
            case 'invitation':
            case 'accepted':
                if ($now > self::tzDateTime("$dateStr $hour:00:00", $tz)){
                    self::removeElement($this->xml);
                }
                break;
            case 'confirmed':
               $oneHour = date_interval_create_from_date_string("1 hour");
                if ($now > date_add($this->dateTime(), $oneHour)){
                    $this->status('feedback');
                }
                break;
            case 'feedback':
                // send email reminder after a couple of days
                break;
            default:
                // nothing to do
        }
    }
    
    
    public function remove(){
        self::removeElement($this->xml);
    }


    public function variables(){
        $mid = $this->id();
        $status = $this->status();
        $game = $this->game();
        $rivalParity = $this->rivalParity();
        $venue = $this->venue();
        // re-check venueHours immediately before display to Player
        $datetime = $this->dateTime();
        $venueHours = isset($venue) ? $venue->facilityHours($game, $datetime) : 0 ;
        $hours = $this->hours() & $venueHours;
        $repWord = $this->rivalRep();
        $rivalRep = strlen($repWord)==0 ? '{unknown}' : $repWord;
        $vars = array(
            'vid'       => $this->vid(),
            'venueName' => $this->venueName(),
            'status'    => $status,
            'game'      => $game,
            'gameName'  => self::gameName($game),
            'day'       => $this->mday(),
            'hrs'       => $hours->bits(),
            'hour'      => self::hr($hours->first()),
            'id'        => $mid,
            'parity'    => parityStr($rivalParity),
            'rivalRep'  => $rivalRep,
            'chatter'   => $this->chat()
        );

        $rival = $this->rival();
        $rivalLink = isset($rival) ? $rival->htmlLink() : NULL;
        $rivalLink = empty($rivalLink) ? $this->rivalName() : $rivalLink;

        switch ($status){
            case 'keen':
                $vars['hour'] = Page::daySpan($hours->roster());
                $vars['rivalCount'] = $this->rivalCount();
                break;
            case 'invitation':
                $vars['hour'] = $this->hourSelect($hours->roster());
                $rivalLink = empty($rivalLink) ? '{a_rival}' : $rivalLink;
                break;
            case 'accepted':
            case 'confirmed':
                $rivalLink = empty($rivalLink) ? '{a_rival}' : $rivalLink;
                break;
            case 'feedback':
                $rivalLink = empty($rivalLink) ? '{my_rival}' : $rivalLink;
                break;
            case 'history':
                $rivalLink = empty($rivalLink) ? '{my_rival}' : $rivalLink;
                $outcome = $this->player->outcome($mid);
                if (isset($outcome)) {
                    $outcomeParity = (string) $outcome['parity'];
                    $vars['parity'] = parityStr($outcomeParity);
                    $vars['thumb'] = $outcome['rep'] == 1 ? Page::THUMB_UP_ICON : Page::THUMB_DN_ICON;
                }
                break;
        }

        $vars['rivalLink'] = $rivalLink;
        return $vars;
    }







    /********************************************************************************
    Return a new DataTime object representing the $match time.

    $match    XML    match data
    ********************************************************************************/
    public function dateTime(){
        if(empty($this->tz())){
            return new datetime();
        }

        $time = $this->time();
        if (!isset($time)){
            $time = $this->date();
        }
        if (!isset($time)){
            $time = 'now';
        }
        return new DateTime($time, timezone_open($this->tz()));
    }



    public function mday(){
        return parent::day($this->tz(), $this->date());
    }
    
    
    private function hourSelect($hrs){
        global $clock24hr;
        if (count($hrs) == 1){
            $hr = $hrs[0];
            $hourbit = pow(2, $hr);
            $hour = self::hr($hr);
            $html = "$hour<input type='hidden' name='hour' value='$hourbit'>";
        } else {
            $html = "<select name='hour' required>\n";
            $html .= "<option selected disabled>time</option>";
            foreach ($hrs as $hr){
                $hourbit = pow(2, $hr);     // $hourbit =(2**$hr);    // php 5.6
                $hour = self::hr($hr);
                $html .= "\t<option value='$hourbit'>$hour</option>\n";
            }
            $html .= "</select>\n";
        }
        return $html;
    }



    static public function parityStr($parity){
        if(is_numeric($parity)){
            $pf = floatval($parity);
            if($pf <= -2){
                return "{much_weaker}";
            } elseif($pf <= -1){
                return "{weaker}";
            } elseif($pf < 1){
                return "{well_matched}";
            } elseif($pf < 2){
                return "{stronger}";
            } else {
                return "{much_stronger}";
            }
        }
        return '{unknown parity}';
    }


}


?>
