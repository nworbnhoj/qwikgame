<?php

require_once 'Page.php';
require_once 'Venue.php';
require_once 'FavoriteListing.php';

class FavoritePage extends Page {

    private $game;
    private $venue;

    public function __construct($templateName='favorite', $language='en'){
        parent::__construct(Page::readTemplate($templateName), $language, $templateName);

        $player = $this->player();
        if (is_null($player)
        || !$player->ok()){
            $this->logout();
            return;
        }

        $this->game = $this->req('game');

        $vid = $this->req('vid');
        if(isset($vid)){
            try {
                $this->venue = new Venue($vid);
            } catch (RuntimeException $e){
                self::alert("{Oops}");
                self::logThrown($e);
                unset($vid);
            }
        }

        if (isset($this->venue)){
            if($this->venue->addGame($this->game)){
                $this->venue->save(TRUE);
                self::logMsg("Added ".$this->game." to $vid");
            }
        } elseif (!is_null($this->req('venue'))){
            if(is_null($this->req('repost'))){
                $this->req('repost', 'favorite.php');
            }
            $query = http_build_query($this->req());
            header("Location: ".self::QWIK_URL."/locate.php?$query");
            return;
        }
    }


    public function processRequest(){
        $player = $this->player();
        $qwik = $this->req('qwik');
        $req = $this->req();
        $result = null;
        switch ($qwik) {
            case "available":
                $result = $this->qwikAvailable($player, $this->venue);
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

        $venue = $this->venue;
        if (!is_null($venue)){
            $vars['vid'] = $venue->id();
            $vars['venue'] = $venue->name();
        } else {
            $vars['vid'] = '';
            $vars['venue'] = '';
        }

        $player = $this->player();
        if (!is_null($player)){
            $playerNick = $player->nick();
            $playerEmail = $player->email();
            $playerName = empty($playerNick) ? $playerEmail : $playerNick;
	    $reckons = $player->reckon("email");
            $historyCount = count($player->matchQuery("match[@status='history']"));

            $vars['message']       .= "{Welcome} <b>$playerName</b>";
            $vars['friendsHidden'] = empty($reckons) ? 'hidden' : ' ';
            $vars['regionOptions'] = $this->regionOptions($player, "\t\t\t");
            $vars['historyHidden'] = $historyCount == 0 ? 'hidden' : '';
            $vars['reputation']    = $player->repWord();
            $vars['thumbs']        = $player->repThumbs();
            $vars['playerNick']    = $playerNick;
            $vars['playerURL']     = $player->url();
            $vars['playerEmail']   = $playerEmail;
            $vars['LOGOUT_ICON']   = self::LOGOUT_ICON;
            $vars['svenue']        = isset($this->venue) ? Venue::svid($this->venue->id()) : "";

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

        $favoriteListing = new FavoriteListing($html);
        $variables['favororiteListing'] = $favoriteListing->make();
        $abilityListing = new AbilityListing($html);
        $variables['abilityListing'] = $abilityListing->make();
        return Html::make($variables); 
    }



///// QWIK SWITCH ///////////////////////////////////////////////////////////



    function qwikAvailable($player, $venue){
        if($this->req('game')
        & $this->req('parity')
        & $this->req('vid')){
            $newID = $player->availableAdd(
                $this->req('game'),
                $this->req('vid'),
                $this->req('parity'),
                $venue->tz(),
                $this->req('smtwtfs') ? $this->req('smtwtfs') : FALSE,
                $this->req()
            );
            if(is_null($venue)){
                $pid = $player->id();
                $vid = $this->req('vid');
                $this->logMsg("Unable to add player to venue:\tpid=$pid\t vid=$vid");
            } else {
                $venue->addPlayer($player->id());
                $venue->save(TRUE);
            }
            return $newID;
        }
        return NULL;
    }


    function qwikRegion($player, $request){
        if(isset($request['game'])
            && isset($request['ability'])
            && isset($request['region'])){
                $player->region($request['game'], $request['ability'], $request['region']);
        }
    }


    function qwikDelete($player, $request){
        $player->deleteData($request['id']);
    }


    function regionOptions($player, $tabs){
        $regions = $this->regions($player);
        $options = '';
        foreach($regions as $region){
               $options .= "$tabs<option value='$region'>$region</option>\n";
        }
        return $options;
    }


}

?>
