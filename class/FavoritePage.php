<?php

require_once 'Page.php';
require_once 'Venue.php';
require_once 'FavoriteList.php';
require_once 'AbilityList.php';
require_once 'Options.php';
require_once 'Locate.php';


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
        $placeId = $this->req('placeid');
        if(isset($vid)){
            if (Venue::exists($vid)){
                try {
                    $this->venue = new Venue($vid);
                } catch (RuntimeException $e){
                    self::alert("{Oops}");
                    self::logThrown($e);
                    unset($vid);
                }
            } elseif (isset($placeId)) {
                $details = Locate::getDetails($placeId);  
                if($details){  // the $vid provided is a valid google placeId
                    $vid = Venue::venueID(
                        $details['name'],
                        $details['locality'],
                        $details['admin1_code'],
                        $details['country_iso']
                    );
                    try {
                        $this->venue = new Venue($vid, TRUE);
                        if($this->venue->ok()){
                            $this->venue->updateAtt('placeid', $placeId);
                            $this->venue->furnish($details);
                        } else {
                          self::alert("Sorry - failed to create new Venue");
                        }
                    } catch (RuntimeException $e){
                        self::alert("{Oops}");
                        self::logThrown($e);
                        unset($vid);
                    }
                }
            }
        }

        if (isset($this->venue)){
            if($this->venue->addGame($this->game)){
                $this->venue->save(TRUE);
                self::logMsg("Added ".$this->game." to $vid");
            }
        }
    }


    public function processRequest(){
        $player = $this->player();
        $qwik = $this->req('qwik');
        $req = $this->req();
        $result = null;
        switch ($qwik) {
            case "register":
                $this->qwikRegister($player, $req);
                $req['parity'] = 'any';
                $req['smtwtfs'] = 16777215;
                // intentional flow thru to available
            case "available":
                $result = $this->qwikAvailable($player, $this->venue, $req);
                break;
            case 'region':
                $result = $this->qwikRegion($player, $req);
                break;
            case 'delete':
                $result = $this->qwikDelete($player, $req);
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

            $vars['regionOptions'] = $regionOptions->make();
            $vars['reputation']    = $player->repWord();
            $vars['thumbs']        = $player->repThumbs();
            $vars['playerNick']    = $playerNick;
            $vars['playerURL']     = $player->url();
            $vars['playerEmail']   = $playerEmail;
            $vars['LOGOUT_ICON']   = self::LOGOUT_ICON;
        }

        $vars['gameOptions']   = $this->gameOptions($this->game, "\t\t");
        
        $loc = Locate::geolocate(array('latitude', 'longitude'));
        $vars['lat'] = isset($loc) && isset($loc['latitude']) ? $loc['latitude'] : NULL ;
        $vars['lng'] = isset($loc) && isset($loc['longitude']) ? $loc['longitude'] : NULL ;

        return $vars;
    }



    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();

        $favoriteList = new FavoriteList($html, 'favorite');
        $vars['favoriteList'] = $favoriteList->make();
        $abilityList = new AbilityList($html, 'ability');
        $vars['abilityList'] = $abilityList->make();
        return parent::make($vars); 
    }



///// QWIK SWITCH ///////////////////////////////////////////////////////////


    function qwikRegister($player, $req){
        if(isset($req['email'])){
            $player->email($req['email']);
        } else {
            $pid = Player::anonID($email);
            self::logMsg("failed to register player: " . self::snip($pid));
        }
    }


    function qwikAvailable($player, $venue, $req){
        if(isset($req['game'])
        && isset($req['vid'])){
            $newID = $player->availableAdd(
                $req['game'],
                $req['vid'],
                isset($req['parity']) ? $req['parity'] : 'any',
                $venue->tz(),
                isset($req['smtwtfs']) ? $req['smtwtfs'] : FALSE,
                $req
            );
            if(is_null($venue)){
                $pid = $player->id();
                $vid = $req['vid'];
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
            && isset($request['parity'])
            && isset($request['region'])){
                $player->region($request['game'], $request['parity'], $request['region']);
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
