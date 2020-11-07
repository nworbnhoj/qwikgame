<?php

require_once 'Page.php';
require_once 'FriendList.php';

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

    public function __construct($templateName='friend'){
        parent::__construct(NULL, $templateName);

        $player = $this->player();
        if (is_null($player)
        || !$player->ok()){
            $this->logout();
            return;
        }
    }


    public function processRequest(){
        $result = parent::processRequest();
        if(!is_null($result)){ return $result; }   // request handled by parent
        
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

        $player = $this->player();
        if (!is_null($player)){
            $playerNick = $player->nick();
            $playerEmail = $player->email();
            $playerName = empty($playerNick) ? $playerEmail : $playerNick;

            $vars['reputation']    = $player->repWord();
            $vars['thumbs']        = $player->repThumbs();
            $vars['playerNick']    = $playerNick;
            $vars['playerURL']     = $player->url();
            $vars['playerEmail']   = $playerEmail;
            $vars['LOGOUT_ICON']   = self::LOGOUT_ICON;
            $vars['paritySelect']  = self::SELECT_PARITY;
        }

        $vars['gameOptions']   = $this->gameOptions($this->game, "\t\t");

        return $vars;
    }





    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();

        $friendList = new FriendList($html, 'friend');
        $vars['friendList'] = $friendList->make();
        return parent::make($vars); 
    }




///// QWIK SWITCH ///////////////////////////////////////////////////////////



function qwikFriend($player, $request){
    if(isset($request['game'])
    && isset($request['rival'])
    && isset($request['parity'])){
        return $player->friend($request['game'], $request['rival'], $request['parity']);
    }
    return NULL;
}


    function qwikDelete($player, $request){
        $player->deleteData($request['id']);
    }


}

?>
