<?php

require_once 'Page.php';
require_once 'FriendListing.php';

class FriendPage extends Page {

    const SELECT_PARITY = 
        "<select name='parity'>
            <option value='2'>{much_stronger}</option>
            <option value='1'>{stronger}</option>
            <option value='0' selected>{well_matched}</option>
            <option value='-1'>{weaker}</option>
            <option value='-2'>{much_weaker}</option>
        </select>";

    private $game;

    public function __construct($templateName='friend', $language='en'){
        parent::__construct(Page::readTemplate($templateName), $language, $templateName);

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
            case 'friend':
                $result = $this->qwikFriend($player, $req);
                break;
            case 'region':
                $result = $this->qwikRegion($player, $req);
                break;
            case 'delete':
                $result = $this->qwikDelete($player, $req);
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
            $vars['thumbs']        = $player->repThumbs();
            $vars['playerNick']    = $playerNick;
            $vars['playerURL']     = $player->url();
            $vars['playerEmail']   = $playerEmail;
            $vars['LOGOUT_ICON']   = self::LOGOUT_ICON;
            $vars['paritySelect']  = self::SELECT_PARITY;

            // special case: new un-activated player
            if (is_null($playerEmail)){
                $vars['message']    .= '{Please activate...}';
                $vars['playerName'] = ' ';
                $vars['playerEmail'] = ' ';
            }
        }

        $vars['gameOptions']   = $this->gameOptions($this->game, "\t\t");

        return $vars;
    }





    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();

        $friendListing = new FriendListing($html);
        $variables['friendListing'] = $friendListing->make();
        return Html::make($variables); 
    }




///// QWIK SWITCH ///////////////////////////////////////////////////////////



function qwikFriend($player, $request){
    if(isset($request['game'])
    && isset($request['rival'])
    && isset($request['parity'])){
        $player->friend($request['game'], $request['rival'], $request['parity']);
    }
}


    function qwikDelete($player, $request){
        $player->deleteData($request['id']);
    }


}

?>
