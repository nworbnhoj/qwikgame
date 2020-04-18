<?php

require_once 'Page.php';

class IndexPage extends Page {

    public function __construct($templateName='index'){
        parent::__construct(NULL, $templateName);
    }

    public function serve(){
        if ($this->player() == NULL){
            parent::serve();
        } else {
            $req = $this->req();
            unset($req['pid']);
            unset($req['token']);
            $query = http_build_query($req);
            header("Location: ".QWIK_URL."match.php?$query", TRUE, 307);
            exit;
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
            case 'logout':
                $result = $this->logout();
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
                $anon->emailWelcome($email, $this->req());
                $this->message("{Check_email}");
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
                    $player->emailLogin($email);
                    $this->message("{Check_email}");
                    return TRUE;
                }
            } else {
                $anon = new Player($pid, TRUE);
                if(isset($anon)){
                    $anon->emailWelcome($email, $this->req());
                    $this->message("{Check_email}");
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
        $language = $this->language();
        
        $variables = parent::variables();

        $variables['playerCount']    = $this->countFiles(PATH_PLAYER);
        $variables['venueCount']     = $this->countFiles(PATH_VENUE);
        $variables['venuesLink']     = "<a href='venues.php?game=squash'>{venues}</a>";
        $variables['venue']          = isset($venue) ? $venue : '';
        $variables['gameOptions']    = $this->gameOptions($game, "\t\t");
        $variables['language']       = $language;
        $variables['languageOptions'] = $this->languageOptions($language);
        
        return $variables;
    }



    private function countFiles($path){
       try {
           return iterator_count(new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS));
       } catch (RuntimeException $e){
            self::logThrown($e);
            self::logMsg("Failed to count files in $path");
        }
    }

}


?>
