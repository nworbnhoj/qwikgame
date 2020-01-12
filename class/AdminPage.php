<?php

require_once 'Page.php';
require_once 'Translation.php';

class AdminPage extends Page {

    private $variables;

    public function __construct($templateName='admin', $language='en'){
        parent::__construct(Html::readTemplate($templateName), $language, $templateName);

        $player = $this->player();
        if (empty($player->admin())){
            $this->logout();
            return;
        }

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



///// QWIK SWITCH ///////////////////////////////////////////////////////////

    private function acceptTranslation($req){
        $key = $this->req('key');
        $lang = $this->req('lang');
        $phrase = $this->req('phrase');
        if (!is_null($key) && !is_null($lang) && !is_null($phrase)){
            $translation = &self::translation();
            $translation->set($key, $lang, $phrase);
            $translation->save();
            $pending = &self::pending();
            $pending->unset($key, $lang);
            $pending->save();
            return true;
        }
        return false;
    }


    private function rejectTranslation($req){
        $key = $this->req('key');
        $lang = $this->req('lang');
        $phrase = $this->req('phrase');
        if (!is_null($key) && !is_null($lang) && !is_null($phrase)){
            $pending = &self::pending();
            $pending->unset($key, $lang);
            $pending->save();
            return true;
        }
        return false;
    }

}

?>
