<?php

require_once 'Page.php';
require_once 'Player.php';
require_once 'Notify.php';

class AccountPage extends Page {

    const LINK_REP = "<a href='info.php#reputation'>{Reputation}</a>";

    public function __construct($templateName='account'){
        parent::__construct(NULL, $templateName);

        $user = $this->user();
        if (is_null($user)
        || !$user->ok()){
            $this->logout();
            return;
        }
    }

    protected function loadUser($uid){
        return new User($uid, FALSE);
    }


    public function processRequest(){
        $user = $this->user();
        $qwik = $this->req('qwik');
        $req = $this->req();
        $result = null;
        switch ($qwik) {
            case 'account':
                $result = $this->qwikAccount($user, $req);
                break;
            case 'quit':
                $user->emailQuit();
                $user->quit();
                // no break - intentional drop thru to logout
            case 'logout':
                if (isset($request['push-endpoint'])){
                    $notify = new Notify($user);
                    $notify->push($request['push-endpoint'], Notify::MSG_NONE);
                }
                $result = $this->logout();
                break;
            default:
                $result =  NULL;
        }

        $user->save();
        return $result;
    }


    public function variables(){
        $vars = parent::variables();

        $vars['MAP_ICON']      = self::MAP_ICON;
        $vars['SEND_ICON']     = self::SEND_ICON;

        $user = $this->user();
        if (!is_null($user)){
            $userNick = $user->nick();
            $userEmail = $user->email();
            $userName = empty($userNick) ? $userEmail : $userNick;
            $notify = new Notify($user);

            $vars['reputation']    = get_class($user) == "Player" ? $user->repWord() : '{good}';
            $vars['reputationLink']= self::LINK_REP;
            $vars['thumbs']        = get_class($user) == "Player" ? $user->repThumbs() : '';
            $vars['playerNick']    = $userNick;
            $vars['playerURL']     = $user->url();
            $vars['playerEmail']   = $userEmail;
            $vars['LOGOUT_ICON']   = self::LOGOUT_ICON;
            $vars['notify-email-checked']  = $notify->is_open($userEmail) ? 'checked' : '';
            $vars['push-endpoint-sack']   = $notify->pushSack();
            $vars['languageOptions'] = $this->languageOptions($user->lang());
        }
        return $vars;
    }



///// QWIK SWITCH ///////////////////////////////////////////////////////////



function qwikAccount($user, $request){
    if(isset($request['nick'])){
        $user->nick($request['nick']);
    }

    if(isset($request['url'])){
        $user->url($request['url']);
    }

    if(isset($request['email'])){
        $email = $request['email'];
        if ($email != $user->email()){
            $user->email($email);
        }
    }

    if(isset($request['lang'])){
        $user->lang($request['lang']);
    }

 
    $notify = new Notify($user);
    $notify->email(
        $user->email(), 
        isset($request['notify-email']) ? Notify::MSG_ALL : Notify::MSG_NONE
    );
    $notify->push(
        isset($request['push-endpoint']) ? $request['push-endpoint'] : NULL,
        isset($request['notify-push']) ? Notify::MSG_ALL : Notify::MSG_NONE,
        isset($request['push-token']) ? $request['push-token'] : NULL,
        isset($request['push-key']) ? $request['push-key'] : NULL
    );
}



}

?>
