<?php

require_once 'Page.php';

class IndexPage extends Page {

    private $alert = '';

    public function __construct($templateName='index', $language='en'){
        parent::__construct(Html::readTemplate($templateName), $language, $templateName);
    }

    public function serve(){
        if ($this->player() == NULL){
            parent::serve();
        } else {
            $query = http_build_query($this->req());
            header("Location: ".self::QWIK_URL."/match.php?$query");
        }
    }



    public function processRequest(){
        $result = null;
        $email = $this->req('email');
        $qwik = $this->req('qwik');
        switch ($qwik) {
            case "available":
                $result = $this->qwikAvailable($email);
                break;
            case "recover":
                $result = $this->qwikRecover($email);
                break;
        }
        return $result;
    }


    function qwikAvailable($email){
        $venue = $this->req('venue');
        $game = $this->req('game');
        if (!isset($venue) 
        || !isset($game) 
        || !isset($email)){
            return FALSE;
        }
        try {
            $pid = Player::anonID($email);
            $anon = new Player($pid, TRUE);
            if(isset($anon)){
                $anon->emailWelcome($email);
                $anon->emailFavourite($this->req(), $email);
                $this->alert = "{Check_email}";
                $result = TRUE;
            }
        } catch (RuntimeException $e){
            self::logThrown($e);
            self::logMsg("Failed to create new Player $pid from IndexPage");
        }
        return FALSE;
    }


    function qwikRecover($email){
        if(!isset($email)){
            return FALSE;
        }
        try{
            $pid = Player::anonID($email);
            if(PLAYER::exists($pid)){
                $player = new Player($pid);
                if($player->ok()){
                    $id = self::snip($pid);
                    self::logMsg("login: recover account $id");
                    // todo rate limit
                    $player->emailLogin();
                    $this->alert = "{Check_email}";
                    return TRUE;
                }
            } else {
                $anon = new Player($pid, TRUE);
                if(isset($anon)){
                    $anon->emailWelcome($email);
                    $this->alert = "{Check_email}";
                    return TRUE;
                }
            }

        } catch (RuntimeException $e){
            self::logThrown($e);
            self::logMsg("Failed to send a recovery email to Player $pid");
        }
        return FALSE;
    }


    public function variables(){
        $venue = $this->req('venue');
        $game = $this->req('game');
        
        $variables = parent::variables();

        $variables['playerCount']    = $this->countFiles('player');
        $variables['venueCount']     = $this->countFiles('venue');
        $variables['venuesLink']     = "<a href='venues.php?game=squash'>{venues}</a>";
        $variables['venue']          = isset($venue) ? $venue : '';
        $variables['gameOptions']    = $this->gameOptions($game, "\t\t");
        $variables['datalists']      = $this->datalists();
        $variables['alert-hidden']   = empty($this->alert) ? 'hidden' : '';
        $variables['alert']          = $this->alert;
        
        return $variables;
    }



    private function emailStash($email, $page, $req, $id, $token){
        $subject = 'qwikgame.org confirm availability';
        $query =  http_build_query($req);
        $game = $req['game'];
        $venue = $req['venue'];

        $msg  = "<p>\n";
        $msg .= "\tPlease click this link to \n";
        $msg .= "\t<a href='".self::QWIK_URL."/$page?$query' target='_blank'>confirm</a>\n";
        $msg .= " that you are available to play <b>$game</b> at <b>$venue</b>.<br>\n";
        $msg .= "\t\t\t</p>\n";
        $msg .= "<p>\n";
        $msg .= "\tIf you did not expect to receive this request, then you can safely ignore and delete this email.\n";
        $msg .= "<p>\n";

        Player::qwikEmail($email, $subject, $msg, $id, $token);
        $this->logEmail('login', $id);
    }



    private function countFiles($path){
        return iterator_count(new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS));
    }

}


?>
