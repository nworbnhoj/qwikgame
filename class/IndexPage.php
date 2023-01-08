<?php

require_once 'Page.php';
require_once 'Locate.php';


class IndexPage extends Page {

  /****************************************************************************
   * POST['email'] is a spam robot honeypot
   * Any data whatsoever in the 'email' field indicates an invalid post
   * The authentic email data should exist in POST['name']
   ****************************************************************************/
  const HONEYPOT = array('email'=>'name');

    protected static function loadUser($uid){
        return parent::loadUser($uid);
    }


    public function __construct($templateName='index'){
      parent::__construct(NULL, $templateName, self::HONEYPOT);
    }
    
    
    public function serve($history=NULL){
        $player = $this->player();
      if(isset($player)){
        header("Location: ".QWIK_URL."match.php", TRUE, 307);
        exit;
      }
        $manager = $this->manager();
      if(isset($manager)){
        header("Location: ".QWIK_URL."booking.php", TRUE, 307);
        exit;
      }
      parent::serve($history);
    }
    

    public function processRequest(){
        $result = null;
        $email = $this->req('email');
        $qwik = $this->req('qwik');
        switch ($qwik) {
            case "available":
                $result = $this->qwikAvailable($email);
                break;
            case "manager":
                $result = $this->qwikManager($email);
                break;
            case "recover":
                $result = $this->qwikRecover($email);
                break;
            default:
                $result = NULL;
        }
        return $result;
    }


    function qwikAvailable($email){
        if (!isset($email)){
            return FALSE;
        }
        try {
            $pid = Player::anonID($email);
            $anon = new Player($pid, TRUE);
            if(isset($anon)){
                $req = array();
                if($this->req('game') && $this->req('vid')){
                  $req['game'] = $this->req('game');
                  $req['vid'] = $this->req('vid');
                }
                $anon->emailWelcome($email, $req);
                $this->message("{Check_email}");
                $result = TRUE;
            }
        } catch (RuntimeException $e){
            self::logThrown($e);
            self::logMsg("Failed to create new Player $pid from IndexPage");
        }
        return FALSE;
    }


    function qwikManager($email){
        if (!isset($email)){
            return FALSE;
        }
        try {
            $mid = Manager::anonID($email);
            $anon = new Manager($mid, TRUE);
            if(isset($anon)){
                $req = array();
                if($this->req('game') && $this->req('vid')){
                  $req['game'] = $this->req('game');
                  $req['vid'] = $this->req('vid');
                }
                $anon->emailWelcome($email, $req);
                $this->message("{Check_email}");
                $result = TRUE;
            }
        } catch (RuntimeException $e){
            self::logThrown($e);
            self::logMsg("Failed to create new Manager $mid from IndexPage");
        }
        return FALSE;
    }


    function qwikRecover($email){
        if(!isset($email)){
            return FALSE;
        }
        try{
            $uid = User::anonID($email);
            if(User::exists($uid)){
                $user = new User($uid);
                if($user->ok()){
                    $id = self::snip($uid);
                    self::logMsg("login: recover account $id");
                    // todo rate limit
                    $user->emailLogin($email);
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
        $vid = $this->req('vid');
        $game = $this->req('game');
        $language = $this->language();
        
        $vars = parent::variables();

        $vars['playerCount']    = $this->countFiles(PATH_PLAYER);
        $vars['venueCount']     = $this->countFiles(PATH_VENUE);
        $vars['vid']            = isset($vid) ? $vid : '';
        $vars['gameOptions']    = $this->gameOptions($game, "\t\t");
        $vars['language']       = $language;
        $vars['languageOptions'] = $this->languageOptions($language);
        
        $loc = Locate::geolocate(array('latitude', 'longitude'));
        $vars['lat'] = isset($loc) && isset($loc['latitude']) ? $loc['latitude'] : NULL ;
        $vars['lng'] = isset($loc) && isset($loc['longitude']) ? $loc['longitude'] : NULL ;
        
        return $vars;
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
