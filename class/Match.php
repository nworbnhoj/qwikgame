<?php

require_once 'class/Qwik.php';
require_once 'class/Hours.php';
require_once 'class/Page.php';


class Match extends Qwik {

    private $player;
    private $id;
    private $xml;

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


    public function rids(){
        return  $this->xml->xpath('rival');
    }


    public function rid(){
        return isset($this->rids()[0]) ? (string) $this->rids()[0] : null;
    }


    public function rival(){
        return new Player($this->rid());
    }


    public function rivalParity(){
        $rid = $this->rid();
        return isset($rid) ? (string) $rid['parity'] : null;
    }


    public function rivalRep(){
        $rid = $this->rid();
        return isset($rid) ? (string) $rid['rep'] : null;
    }


    public function rivalCount(){
        return count($this->rids());
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


    public function invite($rids, $interrupt=FALSE){
        foreach($rids as $rid){
            $rival = new Player($rid);
            if(!is_null($rival)
            && !empty($rival->email())){
                $inviteHours = $this->hours();
                if (!$interrupt){
                    $rivalHours = $rival->availableHours($this);
                    $rivalHours->include($rival->keenHours($this));
                    $inviteHours->includeOnly($rivalHours);
                }
                if (!$inviteHours->empty()){
                    $inviteMatch = $rival->matchInvite($this, $inviteHours);
                    $inviteMatch->addRival($rid);
                    $rival->emailInvite($inviteMatch->id());
                }
                $rival->save();
            }
        }
    }


    public function accept($acceptHour){
        $rival = new Player($this->rival());
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
                $newMid = newID();
                $this->id($newMid); //make independent from keenMatch
                $this->status('accepted');
                $this->hours($acceptHour);
                $rival->matchInvite($this);
                $rival->emailInvite($newMid);
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


    public function decline(){
        foreach($this->rids() as $rid){
            $rival = new Player($rid);
            if(isset($rival)){
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
            self::removeElement($this->xml);
        }
    }


    private function removeRival($rid){
        $rivalElement = $this->xml->xpath("rival='$rid'");
        self::removeElement($rivalElement[0]);
    }


    public function cancel(){
        $complete = array('feedback','history','cancelled');
        if (in_array($this->status(), $complete)){
            return FALSE;
        }
        $this->status('cancelled');
        $mid = $this->id();
        foreach($this->rids() as $rid){
            $rival = new Player($rid);
            if(isset($rival)){
                $match = $rival->matchID($mid);
                if($match->cancel()){
                    $rival->emailCancel($match);
                    $rival->save();
                }
            }
        }
//        self::removeElement($this);
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
        global $THUMB_UP_ICON, $THUMB_DN_ICON;
        $status = $this->status();
        $game = $this->game();
        $rival = $this->rival();
        $parity = $this->rivalParity();
        $hours = $this->hours();
        $rivalLink = $rival->htmlLink();
        $repWord = $this->rivalRep();
        $vars = array(
            'vid'       => $this->vid(),
            'venueName' => $this->venueName(),
            'status'    => $status,
            'game'      => $game,
            'gameName'  => self::games()[$game],
            'day'       => $this->mday(),
            'hrs'       => $hours->bits(),
            'hour'      => self::hr($hours->first()),
            'id'        => $this->id(),
            'parity'    => Page::parityStr($parity),
            'rivalLink' => empty($rivalLink) ? '' : ", $rivalLink",
            'rivalRep'  => strlen($repWord)==0 ? '' : " with a $repWord reputation"
        );
        switch ($status){
            case 'keen':
                $vars['hour'] = Page::daySpan($hours->list());
                $vars['rivalCount'] = $this->rivalCount();
                break;
            case 'invitation':
                $vars['hour'] = $this->hourSelect($hours->list());
                break;
            case 'history':
                $outcome = $this->player->outcome($matchID);
                if (null !== $outcome) {
                    $vars['parity'] = parityStr($outcome['parity']);
                    $vars['thumb'] = $outcome['rep'] == 1 ? $THUMB_UP_ICON : $THUMB_DN_ICON;
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
