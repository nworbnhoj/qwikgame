<?php

require_once 'Page.php';

class AccountPage extends Page {

    const LINK_REP = "<a href='info.php#reputation'>{Reputation}</a>";

    public function __construct($templateName='account'){
        parent::__construct(NULL, $templateName);

        $player = $this->player();
        if (is_null($player)
        || !$player->ok()){
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
            case 'account':
                $result = $this->qwikAccount($player, $req);
                break;
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

        $vars['MAP_ICON']      = self::MAP_ICON;
        $vars['SEND_ICON']     = self::SEND_ICON;
        $vars['alert-hidden']  = 'hidden';

        $player = $this->player();
        if (!is_null($player)){
            $playerNick = $player->nick();
            $playerEmail = $player->email();
            $playerName = empty($playerNick) ? $playerEmail : $playerNick;

            $vars['reputation']    = $player->repWord();
            $vars['reputationLink']= self::LINK_REP;
            $vars['thumbs']        = $player->repThumbs();
            $vars['playerNick']    = $playerNick;
            $vars['playerURL']     = $player->url();
            $vars['playerEmail']   = $playerEmail;
            $vars['LOGOUT_ICON']   = self::LOGOUT_ICON;
        }
        return $vars;
    }



///// QWIK SWITCH ///////////////////////////////////////////////////////////



function qwikAccount($player, $request){
    if(isset($request['nick'])){
        $player->nick($request['nick']);
    }

    if(isset($request['url'])){
        $player->url($request['url']);
    }

    if(isset($request['email'])){
        $email = $request['email'];
        if ($email != $player->email()){
            $player->emailChange($email);
        }
    }

    if(isset($request['lang'])){
        $player->lang($request['lang']);
    }

    if(isset($request['account']) && ($request['account'] === 'quit')) {
        $player->emailQuit();
        $player->quit();
        $this->logout();

        header("Location: ".self::QWIK_URL);
    }
}



}

?>
