<?php

require_once 'Page.php';
require_once 'Venue.php';
require_once 'FavoriteListing.php';
require_once 'AbilityListing.php';
require_once 'Options.php';


class FavoritePage extends Page {

    private $game;
    private $venue;

    public function __construct($templateName='favorite'){
        parent::__construct(NULL, $templateName);

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
            header("Location: ".QWIK_URL."locate.php?$query");
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

        $vars['hourRows']      = $this->hourRows();
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
            $regionOptions = new Options($this->regions(), Options::VALUE_TEMPLATE);

            $vars['message']       .= "{Welcome} <b>$playerName</b>";
            $vars['regionOptions'] = $regionOptions->make();
            $vars['reputation']    = $player->repWord();
            $vars['thumbs']        = $player->repThumbs();
            $vars['playerNick']    = $playerNick;
            $vars['playerURL']     = $player->url();
            $vars['playerEmail']   = $playerEmail;
            $vars['LOGOUT_ICON']   = self::LOGOUT_ICON;
            $vars['svenue']        = isset($this->venue) ? Venue::svid($this->venue->id()) : "";
        }

        $vars['gameOptions']   = $this->gameOptions($this->game, "\t\t");

        return $vars;
    }



    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();

        $favoriteListing = new FavoriteListing($html, 'favorite');
        $vars['favoriteListing'] = $favoriteListing->make();
        $abilityListing = new AbilityListing($html, 'ability');
        $vars['abilityListing'] = $abilityListing->make();
        return parent::make($vars); 
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


    function hourRows(){
        $hourRows = '';
        $days = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');
        $tabs = "\t\t\t\t";
        foreach($days as $day){
            $bit = 1;
            $hourRows .= "$tabs<tr>\n";
            $hourRows .= "$tabs\t<input name='$day' type='hidden' value='0'>\n";
            $hourRows .= "$tabs\t<th class='tr-toggle'>$day</th>\n";
            for($hr24=0; $hr24<=23; $hr24++){
                if (($hr24 < 6) | ($hr24 > 20)){
                    $hidden = 'hidden';
                } else {
                    $hidden = '';
                }
                if ($hr24 <= 12){
                    $hr12 = $hr24;
                } else {
                    $hr12 = $hr24-12;
                }
                $hourRows .= "$tabs\t<td class='toggle' bit='$bit' $hidden>$hr12</td>\n";
                $bit = $bit * 2;
            }
            $hourRows .= "$tabs</tr>\n";
        }
        return $hourRows;
    }


}

?>
