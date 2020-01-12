<?php

require_once 'Page.php';

class AccountPage extends Page {

    const LINK_REP = "<a href='info.php#reputation'>{Reputation}</a>";

    public function __construct($templateName='account', $language='en'){
        parent::__construct(Html::readTemplate($templateName), $language, $templateName);

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
            case 'login':
                $email = $this->req('email');
                if(isset($email)
                && $email != $player->email()){
                    $player->email($email);
                    if (!headers_sent()){
                        $url = $player->authURL(Player::MINUTE);
                        header("Location: $url");
                    }
                }
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

        $vars['datalists']     = $this->datalists();
        $vars['MAP_ICON']      = self::MAP_ICON;
        $vars['SEND_ICON']     = self::SEND_ICON;
        $vars['alert-hidden']  = 'hidden';

        $player = $this->player();
        if (!is_null($player)){
            $playerNick = $player->nick();
            $playerEmail = $player->email();
            $playerName = empty($playerNick) ? $playerEmail : $playerNick;
	    $reckons = $player->reckon("email");
            $historyCount = count($player->matchQuery("match[@status='history']"));

            $vars['message']       .= "{Welcome} <b>$playerName</b>";
            $vars['friendsHidden'] = empty($reckons) ? 'hidden' : ' ';
            $vars['historyHidden'] = $historyCount == 0 ? 'hidden' : '';
            $vars['reputation']    = $player->repWord();
            $vars['reputationLink']= self::LINK_REP;
            $vars['thumbs']        = $player->repThumbs();
            $vars['playerNick']    = $playerNick;
            $vars['playerURL']     = $player->url();
            $vars['playerEmail']   = $playerEmail;
            $vars['LOGOUT_ICON']   = self::LOGOUT_ICON;

            // special case: new un-activated player
            if (is_null($playerEmail)){
                $vars['message']    .= '{Please activate...}';
                $vars['playerName'] = ' ';
                $vars['playerEmail'] = ' ';
            }
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
