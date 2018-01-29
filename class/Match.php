<?php

require_once 'Qwik.php';
require_once 'Hours.php';
require_once 'Page.php';


class Match extends Qwik {

    private $player;
    private $xml;
    private $rivalElements;    // temp variable for performance

    public function __construct($player, $xml){
        parent::__construct();
        $this->player = $player;
        $this->xml = $xml;
    }


    public function init($status, $game, $venue, $date, $hours, $id=NULL){

        $id = is_null($id) ? self::newID() : $id;
        $this->xml->addAttribute('id', $id);

        $this->xml->addAttribute('status', $status);
        $this->xml->addAttribute('game', $game);
        $this->xml->addAttribute('date', $date->format('d-m-Y'));
        $this->xml->addAttribute('hrs', $hours->bits());
        $v = $this->xml->addChild('venue', $venue->id());
        $v->addAttribute('tz', $venue->tz());
    }


    public function copy($match){
        $this->xml->addAttribute('id', $match->id());
        $this->xml->addAttribute('status', $match->status());
        $this->xml->addAttribute('game', $match->game());
        $this->xml->addAttribute('date', $match->date());
        $this->xml->addAttribute('hrs', $match->hours()->bits());
        $v = $this->xml->addChild('venue', $match->vid());
        $v->addAttribute('tz', $match->tz());
    }


    public function addRival($rid, $parity=NULL, $rep=NULL, $name=NULL){
        $rival = $this->xml->addChild('rival', $rid);
        $rival->addAttribute('parity', $parity);
        $rival->addAttribute('rep', $rep);
        $rival->addAttribute('name', $name);
        $this->rivalElements = null; //reset
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
            : null;
    }


    public function rid($index=0){
        $rivalElement = $this->rivalElement($index);
        return isset($rivalElement)
            ? (string) $rivalElement
            : null ;
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
        $rid = $this->rid($index);
        return isset($rid) ? new Player($rid) : null;
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
        return isset($element) ? (string) $element['parity'] : null;
    }


    public function rivalRep($index=0){
        $element = $this->rivalElement($index);
        return isset($element) ? (string) $element['rep'] : null;
    }


    public function rivalCount(){
        return count($this->rivalElements());
    }


    public function time($date=NULL, $hour=NULL){
        if(!is_null($date) && !is_null($time)){
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
        return (string) $this->xml->venue;
    }


    public function invite($rids){
        foreach($rids as $rid => $email){
            $rival = new Player($rid);
            if($rival->exists()){
                $parity = $this->player->parity($rival, $game);
                $hours = $this->hours();
                $rivalMatch = is_null($email)
                    ? $rival->matchInvite($this, $parity)
                    : $rival->matchAdd($this, $parity, $hours, $email);
                if(!is_null($rivalMatch)){
                    $this->addRival($rid);
                }
            }
        }
    }


    public function accept($acceptHour){
        $rival = $this->rival(0);
        if (!$rival){
            return FALSE;
        }

        $rivalMatch = $rival->matchID($this->id());
        if (!$rivalMatch){
            return FALSE;
        }

        $rivalStatus = $rivalMatch->status();
        switch ($rivalStatus) {
            case 'keen':
                $newMid = self::newID();
                $this->id($newMid); //make independent from keenMatch
                $this->status('accepted');
                $this->hours($acceptHour);
                $rival->matchInvite($this);
                $rival->emailInvite($this);
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
    }


    public function confirm($date, $hour){
        $this->status('confirmed');
        $this->time($date, $hour);
        $this->player->emailConfirm($this->id());
    }


    private function removeRival($rid){
        $rivalElement = $this->xml->xpath("rival='$rid'");
        self::removeElement($rivalElement[0]);
        $this->rivalElements = null; // reset
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
        switch ($this->status()) {
            case 'confirmed':
                foreach($rivals as $rival){    // usually only one rival
                    $rival->emailCancel($this);
                }
                // break; intentional drop-thru
            case 'keen':
                foreach($rivals as $rival){
                    $rival->matchCancel($mid);
                }
                break;
            case 'invitation':    // not used - handled by decline()
                foreach($rivals as $rival){
                    $rival->matchDecline($mid);
                }
        }
    }


    public function decline(){
        $rivals = $this->rivals();
        foreach($rivals as $rival){
            $match = $rival->match($mid);
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

        $rivalElements = $this->rivalElements();
        foreach($rivalElements as $element){
            self::removeElement($element);
        }
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
        $rival = $this->rival();
        $parity = $this->rivalParity();
        $parityStr = Page::parityStr($parity);
        $hours = $this->hours();
        $rivalLink = isset($rival) ? $rival->htmlLink() : null;
        $repWord = $this->rivalRep();
        $vars = array(
            'vid'       => $this->vid(),
            'venueName' => $this->venueName(),
            'status'    => $status,
            'game'      => $game,
            'gameName'  => self::qwikGames()[$game],
            'day'       => $this->mday(),
            'hrs'       => $hours->bits(),
            'hour'      => self::hr($hours->first()),
            'id'        => $mid,
            'parity'    => strlen($parityStr)==0 ? '{unknown parity}' : $parityStr,
            'rivalRep'  => strlen($repWord)==0 ? '{unknown}' : $repWord
        );
        switch ($status){
            case 'keen':
                $vars['hour'] = Page::daySpan($hours->list());
                $vars['rivalCount'] = $this->rivalCount();
                break;
            case 'invitation':
                $vars['hour'] = $this->hourSelect($hours->list());
                $vars['rivalLink'] = empty($rivalLink) ? '{a_rival}' : $rivalLink;
                break;
            case 'accepted':
                $vars['rivalLink'] = empty($rivalLink) ? '{a_rival}' : $rivalLink;
                break;
            case 'feedback':
                $vars['rivalLink'] = empty($rivalLink) ? '{my_rival}' : $rivalLink;
                break;
            case 'history':
                $vars['rivalLink'] = empty($rivalLink) ? '{my_rival}' : $rivalLink;
                $outcome = $this->player->outcome($mid);
                if (isset($outcome)) {
                    $vars['parity'] = Page::parityStr($outcome['parity']);
                    $vars['thumb'] = $outcome['rep'] == 1 ? Page::THUMB_UP_ICON : Page::THUMB_DN_ICON;
                }
                break;
        }
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


}


?>
