<?php

require_once 'Page.php';
require_once 'Player.php';
require_once 'Notify.php';

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
                if (isset($request['push-endpoint']){
                    $notify = new Notify($player);
                    $notify->push($request['push-endpoint'], Notify::MSG_NONE);
                }
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

        $player = $this->player();
        if (!is_null($player)){
            $playerNick = $player->nick();
            $playerEmail = $player->email();
            $playerName = empty($playerNick) ? $playerEmail : $playerNick;
            $notify = new Notify($player);

            $vars['reputation']    = $player->repWord();
            $vars['reputationLink']= self::LINK_REP;
            $vars['thumbs']        = $player->repThumbs();
            $vars['playerNick']    = $playerNick;
            $vars['playerURL']     = $player->url();
            $vars['playerEmail']   = $playerEmail;
            $vars['LOGOUT_ICON']   = self::LOGOUT_ICON;
            $vars['notify-email-checked']  = $notify->is_open($playerEmail) ? 'checked' : '';
            $vars['push-endpoint-sack']   = $notify->pushSack();
            $vars['languageOptions'] = $this->languageOptions($player->lang());
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

 
    $notify = new Notify($player);
    $notify->email(
        $player->email(), 
        isset($request['notify-email']) ? Notify::MSG_ALL : Notify::MSG_NONE
    );
    $notify->push(
        isset($request['push-endpoint']) ? $request['push-endpoint'] : NULL,
        isset($request['notify-push']) ? Notify::MSG_ALL : Notify::MSG_NONE,
        isset($request['push-token']) ? $request['push-token'] : NULL,
        isset($request['push-key']) ? $request['push-key'] : NULL
    );


    if(isset($request['account']) && ($request['account'] === 'quit')) {
        $player->emailQuit();
        $player->quit();
        $this->logout();

        header("Location: ".QWIK_URL, TRUE, 307);
        exit;
    }
}



}

?>
