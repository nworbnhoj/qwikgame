<?php

require_once 'Page.php';
require_once 'Translation.php';
require_once 'PendingList.php';
require_once 'ShaList.php';

class AdminPage extends Page {

    private $translation;
    private $pending;
    private $variables;

    public function __construct($templateName='admin'){
        parent::__construct(NULL, $templateName);

        $user = $this->user();
        if (is_null($user)
        || empty($user->admin())){
            $this->logout();
            return;
        }

        $this->translation = new Translation(self::$translationFileName);
        $this->pending = new Translation('pending.xml');
        $this->variables = parent::variables();
    }


    protected function loadUser($uid){
        return new User($uid, FALSE);
    }


    public function processRequest(){
        $result = parent::processRequest();
        if(!is_null($result)){ return $result; }   // request handled by parent
        
        $user = $this->user();
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

        $user = $this->user();
        $userNick = $user->nick();
        $userEmail = $user->email();
        $userName = empty($userNick) ? $userEmail : $userNick;

        $vars['LOGOUT_ICON']   = self::LOGOUT_ICON;
        $vars['TICK_ICON']     = self::TICK_ICON;



        $vars['phpinfo'] = $this->phpInfo();
        return $vars;
    }


    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();

        $pendingList = new PendingList($html, 'pending');
        $vars['pendingList'] = $pendingList->make();        

        $shaList = new ShaList($html, 'sha');
        $vars['shaList'] = $shaList->make();        
        
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
    
    
    private function htmlSHA256(){
    
    }
    
    
    private function phpInfo(){
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_end_clean();
        $matches = array(); 
        preg_match("/(?:<body>)([\s\S]*)(?:<\/body>)/", $phpinfo, $matches);
        return $matches[0];    
    }

}

?>
