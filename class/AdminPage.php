<?php

require_once 'Page.php';
require_once 'Translation.php';
require_once 'PendingListing.php';

class AdminPage extends Page {

    private $translation;
    private $pending;
    private $variables;

    public function __construct($templateName='admin'){
        parent::__construct(NULL, $templateName);

        $player = $this->player();
        if (empty($player->admin())){
            $this->logout();
            return;
        }

        $this->translation = new Translation(self::$translationFileName);
        $this->pending = new Translation('pending.xml');
        $this->variables = parent::variables();
    }


    public function processRequest(){
        $player = $this->player();
        $admin = $this->req('admin');
        $req = $this->req();
        $result = null;
        switch ($admin) {
            case 'acceptTranslation':
                $result = $this->acceptTranslation($req);
                break;
            case 'rejectTranslation':
                $result = $this->rejectTranslation($req);
                break;
            default:
                $result =  NULL;
        }

        return $result;
    }


    public function variables(){
        $vars = parent::variables();

        $player = $this->player();
        $playerNick = $player->nick();
        $playerEmail = $player->email();
        $playerName = empty($playerNick) ? $playerEmail : $playerNick;

        $vars['alert-hidden']  = 'hidden';
        $vars['message']       = "{Welcome} <b>$playerName</b>";
        $vars['LOGOUT_ICON']   = self::LOGOUT_ICON;
        $vars['TICK_ICON']     = self::TICK_ICON;


        // phpinfo
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_end_clean();
        $matches = array(); 
        preg_match("/(?:<body>)([\s\S]*)(?:<\/body>)/", $phpinfo, $matches);
        $vars['phpinfo'] = $matches[0];
        return $vars;
    }


    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();

        $pendingListing = new PendingListing($html, 'pending');
        $vars['pendingListing'] = $pendingListing->make();
        return parent::make($vars); 
    }



///// QWIK SWITCH ///////////////////////////////////////////////////////////

    private function acceptTranslation($req){
        $key = $this->req('key');
        $lang = $this->req('lang');
        $phrase = $this->req('phrase');
        if (!isset($key, $lang, $phrase)){
            return false;
        }

        $translation = $this->translation;
        $translation->set($key, $lang, $phrase);
        if ($translation->save()){
            $this->pending->unset($key, $lang);
            if (!$this->pending->save()){
                self::logMsg("failed to unset pending translation for $key $lang $phrase.");
                return false;
            }
        } else {
            self::logMsg("failed to accept translation for $key $lang $phrase.");
            return false;
        }
        return true;
    }


    private function rejectTranslation($req){
        $key = $this->req('key');
        $lang = $this->req('lang');
        $phrase = $this->req('phrase');
        if (!isset($key, $lang, $phrase)){
            return false;
        }

        $this->pending->unset($key, $lang);
        if (!$this->pending->save()){
            self::logMsg("failed to unset pending translation for $key $lang $phrase.");
            return false;
        }
        return true;
    }

}

?>
