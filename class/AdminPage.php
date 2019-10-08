<?php

require_once 'Page.php';

class AdminPage extends Page {

    public function __construct($template='admin'){
        parent::__construct($template);

        $player = $this->player();
        if (empty($player->admin())){
            $this->logout();
            return;
        }
    }


    public function processRequest(){
        $player = $this->player();
        $qwik = $this->req('qwik');
        $req = $this->req();
        $result = null;
        switch ($qwik) {
            case 'logout':
                $result = $this->logout();
                break;
            default:
                $result =  NULL;
        }

        $player->save();
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

        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_end_clean();
        $matches = array();
        preg_match("/(?:<body>)([\s\S]*)(?:<\/body>)/", $phpinfo, $matches);
        $vars['phpinfo'] = $matches[0];

        return $vars;
    }





}

?>
