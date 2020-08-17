<?php

require_once 'Qwik.php';
require_once 'Player.php';
require_once 'Match.php';
require_once 'Email.php';
require_once 'Html.php';
require_once 'Push.php';


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
        } else if (isset($msg) && $msg != (int)$xml) {
            unset($xml);  // replace the current path or add a new path
            $xml = $this->xml->addChild("path", htmlspecialchars($msg));
            $xml->addAttribute('email', $email);
        }
        return $xml;
    }



    /* Set and Get the SimpleXML element representing an push notification path.
     * @param $endpoint the push subscription endpoint address to receive notifications
     * @param $msg a bitfield representing the specific notifications for this path
     * @param $token the push subscription token
     * @param $key the push subscription key
     * @return a SimpleXML Element representing a push notification path; 
     * or an array of Elements if $endpoint omitted.
     */
    public function push($endpoint=NULL, $msg=NULL, $token=NULL, $key=NULL){
        if (is_null($endpoint)){
            return $this->xml->xpath("//path[@endpoint]");
        }

        $paths = $this->xml->xpath("//path[@endpoint='$endpoint']");
        $xml = empty($paths) ? NULL : $paths[0] ;

        if (isset($xml) && $msg === self::MSG_NONE){
            self::removeElement($xml);  // remove the current path
            $xml = NULL;
        } else if (isset($msg, $token, $key) && $msg != (string)$xml) {
            unset($xml);  // replace the current path or add a new path
            $xml = $this->xml->addChild("path", htmlspecialchars($msg));
            $xml->addAttribute('endpoint', $endpoint);
            $xml->addAttribute('token', $token);
            $xml->addAttribute('key', $key);
        }
        return $xml;
    }


    public function is_open($address, $msg=self::MSG_ALL){
        $xml = $this->email($address);
        $xml = isset($xml) ? $xml : $this->push($address);
        return isset($xml) ? $msg && ((int)$xml) : FALSE ;
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


    private function removeDeadEnds($deadEnds){
        foreach ($deadEnds as $endpoint){
            $this->push($endpoint, self::MSG_NONE);
        }
        $this->player->save();
    }



////////// PUBLIC SEND ///////////////////////////////////////


    public function sendInvite($match, $email){
        $email = $this->is_open($email, self::MSG_INVITE) ? $email : $this->player->email();
        if ($this->is_open($email, self::MSG_INVITE)){
            $this->emailInvite($match, $email);
        }
        $this->pushInvite($match, $this->subscriptions(self::MSG_INVITE));
    }


    public function sendConfirm($mid){
        $email = $this->player->email(); 
        if ($this->is_open($email, self::MSG_CONFIRM)){
            $this->emailConfirm($mid, $email);
        }
        $this->pushConfirm($mid, $this->subscriptions(self::MSG_CONFIRM));
    }


    public function sendMsg($msg, $match){
        $email = $this->player->email();
        if ($this->is_open($email, self::MSG_MSG)){
            $this->emailMsg($msg, $match, $email);
        }
        $this->pushMsg($msg, $match, $this->subscriptions(self::MSG_MSG));
    }


    public function sendCancel($match){
        $email = $this->player->email();
        if ($this->is_open($email, self::MSG_CANCEL)){
            $this->emailCancel($match, $email);
        }
        $this->pushCancel($match, $this->subscriptions(self::MSG_CANCEL));
    }


////////// PRIVATE EMAIL ///////////////////////////////////////


    private function emailInvite($match, $email=NULL){
        $email = is_null($email) ? $this->player->email() : $email ;
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
            "venueName"  => Venue::svid($match->venue()),
            "authLink"   => $this->player->authLink(self::DAY)
        );
        $email = new Email($vars, $this->language());
        $email->send();
    }


    private function emailCancel($match, $address){
        $datetime = $match->dateTime();
        $time = date_format($datetime, "ga D");
        $game = $match->game();
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


////////// PRIVATE PUSH ///////////////////////////////////////



    private function subscription($path){
        return [
            "endpoint" => (string) $path['endpoint'],
            "keys"     => [
                'p256dh' => (string) $path['key'],
                'auth'   => (string) $path['token']
            ],
       ];
    }


    private function subscriptions($msg){
        $subscriptions = array();
        foreach($this->push() as $path){
            if ($this->is_open($path['endpoint'], $msg)){
                $subscriptions[] = $this->subscription($path);
            }
        }
        return $subscriptions;
    }


    private function pushInvite($match, $subscriptions){
        $lang = $this->player->lang();
        $vars = array(
            "gameName"  => self::gameName($match->game()),
            "day"       => $match->mday(),
            "venueName" => $match->venueName()
        );
        $push = new Push(
                    $subscriptions,
                    $this->pushMake("{PushInviteTitle}", $lang, $vars),
                    $this->pushMake("{PushInviteBody}", $lang, $vars),
                    Push::INVITE_OPTIONS
                );
        if(!$push->send()){
            $this->removeDeadEnds($push->deadEnds());
        }
    }


    private function pushConfirm($mid, $subscriptions){
        $lang = $this->player->lang();
        $match = $this->player->matchID($mid);
        $rivalName = $match->rival()->nick();
        $vars = array(
            "gameName"  => self::gameName($match->game()),
            "time"      => date_format($match->dateTime(), "ga D"),
            "venueName" => $match->venueName(),
            "rivalName"  => empty($rivalName) ? '' : $rivalName
        );
        $push = new Push(
                    $subscriptions,
                    $this->pushMake("{PushConfirmTitle}", $lang, $vars),
                    $this->pushMake("{PushConfirmBody}", $lang, $vars),
                    Push::CONFIRM_OPTIONS
                );
        if(!$push->send()){
            $this->removeDeadEnds($push->deadEnds());
        }
    }


    private function pushMsg($message, $match, $subscriptions){
        $lang = $this->player->lang();
        $rivalName = $match->rival()->nick();
        $vars = array(
            "gameName"  => self::gameName($match->game()),
            "time"      => date_format($match->dateTime(), "ga D"),
            "venueName" => $match->venueName(),
            "message"   => $message,
            "rivalName"  => empty($rivalName) ? '{rival}' : $rivalName
        );
        $push = new Push(
                    $subscriptions,
                    $this->pushMake("{PushMsgTitle}", $lang, $vars),
                    $this->pushMake("{PushMsgBody}", $lang, $vars),
                    Push::MSG_OPTIONS
                );
        if(!$push->send()){
            $this->removeDeadEnds($push->deadEnds());
        }
    }


    private function pushCancel($match, $subscriptions){
        $lang = $this->player->lang();
        $vars = array(
            "gameName"  => self::gameName($match->game()),
            "time"      => date_format($match->dateTime(), "ga D"),
            "venueName" => $match->venueName()
        );
        $push = new Push(
                    $subscriptions,
                    $this->pushMake("{PushCancelTitle}", $lang, $vars),
                    $this->pushMake("{PushCancelBody}", $lang, $vars),
                    Push::CANCEL_OPTIONS
                );
        if(!$push->send()){
            $this->removeDeadEnds($push->deadEnds());
        }
    }


    private function pushMake($phrase, $lang, $vars){
        $html = new Html($phrase, $lang);
        $txt = $html->translate($phrase);
        $txt = $html->populate($txt, $vars);
        $txt = $html->translate($txt);
        return $txt;
    }


}


?>
