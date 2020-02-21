<?php

require_once 'Qwik.php';
require_once 'Player.php';
require_once 'Match.php';
require_once 'Email.php';


class Notify extends Qwik {


    const CH_EMAIL = 0b0000000000000001;
    const CH_PUSH  = 0b0000000000000010;

    
    const MSG_DEFAULT  = PHP_INT_MAX;


    const OPEN = 0b1111111111111111;
    const SHUT = 0b0000000000000000;

    private $player;
    private $xml;

    public function __construct($player){
        parent::__construct();
        $this->player = $player;
        $this->xml = $player->notifyXML();
    }



    private function pid(){
        return (string) $this->player->id();
    }


    private function language(){
        return $this->player->lang();
    }


    function fallback(){
        return (int) $this->xml['default'];
    }


    /**************************************************************
    * @param $channel [ Notify::CH_EMAIL | Notify::CH_PUSH ]
    * @param $state   [ NULL | Notify::OPEN | Notify::SHUT ]
    * @param $msg     [ 'default' ]
    *
    ***************************************************************/
    public function open($channel, $state=NULL, $msg='default'){
        $default = ($msg === 'default');
        if(!$default){  // temporary warning
            self::logMsg("Not implemented: Notify message = $msg");
            return FALSE;
        }

        if(!is_null($state)){
            $current = $default ? $this->fallback() : 0 ;
            $revised = ($current & ~$channel) | ($state & $channel);
            if ($default){
                $this->xml['default'] = $revised;
            } else {
                // handle bitfields for explicit message types here 
            }
        }

        $fallback = $this->xml['default'];
        $explicit = NULL; // $this->xml->xpath("$msg");
        $bitfield = is_null($explicit) ? $fallback : $explicit ;
         
        return boolval($bitfield & $channel);
    }



////////// PUBLIC SEND ///////////////////////////////////////


    public function sendInvite($match, $email){
        if ($this->open(self::CH_EMAIL)){
            $this->emailInvite($match, $email);
        }
    }


    public function sendConfirm($mid){
        if ($this->open(self::CH_EMAIL)){
            $this->emailConfirm($mid);
        }
    }


    public function sendMsg($msg, $match){
        if ($this->open(self::CH_EMAIL)){
            $this->emailMsg($msg, $match);
        }
    }


    public function sendCancel($match){
        if ($this->open(self::CH_EMAIL)){
            $this->emailCancel($match);
        }
    }


////////// PRIVATE EMAIL ///////////////////////////////////////


    private function emailInvite($match, $email=NULL){
        $email = is_null($email) ? $this->player->email() : $email ;
        $date = $match->dateTime();
        $day = $match->mday();
        $game = $match->game();
        $venueName = $match->venueName();
        $authLink = $this->player->authLink(self::WEEK, array("email"=>$email));
        $paras = array(
            "{You are invited}",
            "{Please accept}"
        );
        $vars = array(
            "subject"    => "{EmailInviteSubject}",
            "paragraphs" => $paras,
            "to"         => $email,
            "gameName"   => self::gameName($game),
            "day"        => $day,
            "venueName"  => $venueName,
            "authLink"   => $authLink
        );
        $email = new Email($vars, $this->language());
        $email->send();
    }


    private function emailConfirm($mid){
        $match = $this->player->matchID($mid);
        $datetime = $match->dateTime();
        $time = date_format($datetime, "ga D");
        $game = $match->game();
        $venueName = $match->venueName();
        $paras = array(
            "{Game is set}",
            "{Need to cancel}",
            "{Have great game}"
        );
        $vars = array(
            "subject"    => "{EmailConfirmSubject}",
            "paragraphs" => $paras,
            "to"         => $this->player->email(),
            "gameName"   => self::gameName($game),
            "time"       => $time,
            "venueName"  => $venueName,
            "authLink"   => $this->player->authLink(self::DAY)
        );
        $email = new Email($vars, $this->language());
        $email->send();
    }


    private function emailMsg($message, $match){
        $datetime = $match->dateTime();
        $time = date_format($datetime, "ga D");
        $game = $match->game();
        $pid = $this->player->id();
        $venueName = Venue::svid($match->venue());
        $paras = array(
            "{game time venue}",
            "{Your rival says...}",
            "{Please reply}"
        );
        $vars = array(
            "subject"    => "{EmailMsgSubject}",
            "paragraphs" => $paras,
            "to"         => $this->player->email(),
            "message"    => $message,
            "gameName"   => self::gameName($game),
            "time"       => $time,
            "venueName"  => $venueName,
            "authLink"   => $this->player->authLink(self::DAY)
        );
        $email = new Email($vars, $this->language());
        $email->send();
    }


    private function emailCancel($match){
        $datetime = $match->dateTime();
        $time = date_format($datetime, "ga D");
        $game = $match->game();
        $pid = $this->player->id();
        $venueName = $match->venueName();
        $vars = array(
            "subject"    => "{EmailCancelSubject}",
            "paragraphs" => array("{Game cancelled}"),
            "to"         => $this->player->email(),
            "gameName"   => self::gameName($game),
            "time"       => $time,
            "venueName"  => $venueName
        );
        $email = new Email($vars, $this->language());
        $email->send();
    }


}


?>
