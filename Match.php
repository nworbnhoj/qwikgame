<?php


class Match {

    private $player;
    private $id;
    private $xml;
    private $log;

    public function __construct($player, $xml){
        $this->player = $player;
        $this->xml = $xml;
        $this->log = $player->log();
    }


    public function init($status, $game, $venue, $date, $hours, $id=NULL){

        $id = is_null($id) ? newID() : $id;
        $this->xml->addAttribute('id', $id);

        $this->xml->addAttribute('status', $status);
        $this->xml->addAttribute('game', $game);
        $this->xml->addAttribute('date', $date->format('d-m-Y'));
        $this->xml->addAttribute('hrs', $hours);
        $v = $this->xml->addChild('venue', $venue['id']);
        $v->addAttribute('tz', $venue['tz']);
    }


    public function copy($match){
        $this->xml->addAttribute('id', $match->id());
        $this->xml->addAttribute('status', $match->status());
        $this->xml->addAttribute('game', $match->game());
        $this->xml->addAttribute('date', $match->date());
        $this->xml->addAttribute('hrs', $match->hrs());
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


    public function hrs($hrs=NULL){
        if(!is_null($hrs)){
            $this->xml['hrs'] = $hrs;
        }
        return $this->xml['hrs'];
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
        return (string) $this->rids()[0];
    }


    public function rival(){
        return new Player($this->rid(), $this->log);
    }


    public function rivalParity(){
        return (string) $this->rids()[0]['parity'];
    }


    public function rivalRep(){
        return (string) $this->rids()[0]['rep'];
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


    public function invite($rids, $interrupt=FALSE){
        foreach($rids as $rid){
            $rival = new Player($rid, $this->log);
            if(!is_null($rival)
            && !empty($rival->email())){
                $inviteHours = $this->hrs();
                if (!interrupt){
                    $availableHours = $rival->availableHours($this, $match);
                    $keenHours = $rival->keenHours($this, $match);
                    $inviteHours = $hours & ($availableHours | $keenHours);
                }
                if ($inviteHours > 0){
                    $inviteMatch = $rival->matchInvite($match, $inviteHours);
                    $inviteMatch->addRival($rid);
                    $rival->emailInvite($invitematch->id());
                }
                $rival->save();
            }
        }
    }


    public function accept($acceptHour){
        $rival = new Player($this->rival(), $this->log);
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
                $this->hrs($acceptHour);
                $rival->matchInvite($this);
                $rival->emailInvite($newMid);
                break;
            case 'accepted':
                $hour = hours($acceptHour)[0];
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
            $rival = new Player($rid, $this->log);
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
            removeElement($this->xml);
        }
    }


    private function removeRival($rid){
        $rivalElement = $this->xml->xpath("rival='$rid'");
        removeElement($rivalElement[0]);
    }


    public function cancel(){
        $complete = array('feedback','history','cancelled');
        if (in_array($this->status(), $complete)){
            return FALSE;
        }
        $this->status('cancelled');
        $mid = $this->id();
        foreach($this->rids() as $rid){
            $rival = new Player($rid, $this->log);
            if(isset($rival)){
                $match = $rival->matchID($mid);
                if($match->cancel()){
                    $rival->emailCancel($match);
                    $rival->save();
                }
            }
        }
//        removeElement($this);
    }


    public function conclude(){
        $tz = $this->tz();
        $now = tzDateTime('now', $tz);
        $dateStr = $this->date();
        $hour = max(hours($this->hrs()));
        switch ($this->status()){
            case 'cancelled':
                $hour = min($hour+6, 24);
            case 'keen':
            case 'invitation':
            case 'accepted':
                if ($now > tzDateTime("$dateStr $hour:00:00", $tz)){
                    removeElement($this->xml);
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
        removeElement($this->xml);
    }





    public function variables(){
    //echo "<br>MATCHVARIABLES<br>";
        global $THUMB_UP_ICON, $THUMB_DN_ICON, $games;
        $status = $this->status();
        $game = $this->game();
        $rival = $this->rival();
        $rivalElement = $match->xpath("rival")[0];
        $parity = $this->rivalParity();
        $hrs = $this->hrs();
        $rivalLink = $rival->htmlLink();
        $repWord = $this->rivalRep();
        $vars = array(
            'vid'       => $this->vid(),
            'venueName' => $this->venueName(),
            'status'    => $status,
            'game'      => $game,
            'gameName'  => $games[$game],
            'day'       => $this->day(),
            'hrs'       => $hrs,
            'hour'      => hr(hours($hrs)[0]),
            'id'        => $this->id(),
            'parity'    => parityStr($parity),
            'rivalLink' => empty($rivalLink) ? '' : ", $rivalLink",
            'rivalRep'  => strlen($repWord)==0 ? '' : " with a $repWord reputation"
        );
        switch ($status){
            case 'keen':
                $vars['hour'] = daySpan($hrs);
                $vars['rivalCount'] = $this->rivalCount();
                break;
            case 'invitation':
                $vars['hour'] = hourSelect(hours($hrs));
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



    public function day(){
        return day($this->tz(), $this->date());
    }


}
