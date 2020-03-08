<?php

require_once 'Qwik.php';
require_once 'Player.php';
require_once 'Match.php';
require_once 'Email.php';


class Notify extends Qwik {    

    const MSG_NONE    = 0b0000000000000000;
    const MSG_INVITE  = 0b0000000000000001;
    const MSG_CONFIRM = 0b0000000000000010;
    const MSG_MSG     = 0b0000000000000100;
    const MSG_CANCEL  = 0b0000000000001000;
    const MSG_ALL     = 0b1111111111111111;

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



    /* Set and Get the SimpleXML element representing an email notification path.
     * @param $email the email address to receive notifications
     * @param $msg a bitfield representing the specific notifications for this path
     * @return a SimpleXML Element representing an email notification path.
     */
    public function email($email, $msg=NULL){
        if (is_null($email)){
            return NULL;
        }

        $paths = $this->xml->xpath("//path[@email='$email']");
        $xml = empty($paths) ? NULL : $paths[0] ;

        if (isset($xml) && $msg === self::MSG_NONE){
            self::removeElement($xml);  // remove the current path
            $xml = NULL;
        } else if (isset($msg) && $msg != (string)$xml) {
            unset($xml);  // replace the current path or add a new path
            $xml = $this->xml->addChild("path", $msg);
            $xml->addAttribute('email', $email);
        }
        return $xml;
    }



    /* Set and Get the SimpleXML element representing an push notification path.
     * @param $endpoint the push subscription endpoint address to receive notifications
     * @param $msg a bitfield representing the specific notifications for this path
     * @param $token the push subscription token
     * @param $key the push subscription key
     * @return a SimpleXML Element representing an push notification path.
     */
    public function push($endpoint, $msg=NULL, $token=NULL, $key=NULL){
        if (is_null($endpoint)){
            return NULL;
        }

        $paths = $this->xml->xpath("//path[@endpoint='$endpoint']");
        $xml = empty($paths) ? NULL : $paths[0] ;

        if (isset($xml) && $msg === self::MSG_NONE){
            self::removeElement($xml);  // remove the current path
            $xml = NULL;
        } else if (isset($msg, $token, $key) && $msg != (string)$xml) {
            unset($xml);  // replace the current path or add a new path
            $xml = $this->xml->addChild("path", $msg);
            $xml->addAttribute('endpoint', $endpoint);
            $xml->addAttribute('token', $token);
            $xml->addAttribute('key', $key);
        }
        return $xml;
    }


    public function is_open($address){
        $emailXML = $this->email($address);
        if (isset($emailXML)){
            return TRUE;
        }
        $pushXML = $this->push($address);
        if (isset($pushXML)){
           return TRUE;
        }
        return FALSE;
    }


    public function pushSack(){
        $sack='';
        $paths = $this->xml->xpath("//path[@endpoint]");
        foreach($paths as $path){
            $sack .= $path['endpoint'];
            $sack .= "   ";  // spacer for human readability
        }
        return $sack;
    }



////////// PUBLIC SEND ///////////////////////////////////////


    public function sendInvite($match, $email){
        $email = $this->is_open($email) ? $email : $this->player->email();
        if ($this->is_open($email)){
            $this->emailInvite($match, $email);
        }
    }


    public function sendConfirm($mid){
        $email = $this->player->email(); 
        if ($this->is_open($email)){
            $this->emailConfirm($mid, $email);
        }
    }


    public function sendMsg($msg, $match){
        $email = $this->player->email();
        if ($this->is_open($email)){
            $this->emailMsg($msg, $match, $email);
        }
    }


    public function sendCancel($match){
        $email = $this->player->email();
        if ($this->is_open($email)){
            $this->emailCancel($match, $email);
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


    private function emailConfirm($mid, $address){
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
            "to"         => $address,
            "gameName"   => self::gameName($game),
            "time"       => $time,
            "venueName"  => $venueName,
            "authLink"   => $this->player->authLink(self::DAY)
        );
        $email = new Email($vars, $this->language());
        $email->send();
    }


    private function emailMsg($message, $match, $address){
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
            "to"         => $address,
            "message"    => $message,
            "gameName"   => self::gameName($game),
            "time"       => $time,
            "venueName"  => $venueName,
            "authLink"   => $this->player->authLink(self::DAY)
        );
        $email = new Email($vars, $this->language());
        $email->send();
    }


    private function emailCancel($match, $address){
        $datetime = $match->dateTime();
        $time = date_format($datetime, "ga D");
        $game = $match->game();
        $pid = $this->player->id();
        $venueName = $match->venueName();
        $vars = array(
            "subject"    => "{EmailCancelSubject}",
            "paragraphs" => array("{Game cancelled}"),
            "to"         => $address,
            "gameName"   => self::gameName($game),
            "time"       => $time,
            "venueName"  => $venueName
        );
        $email = new Email($vars, $this->language());
        $email->send();
    }


}


?>
