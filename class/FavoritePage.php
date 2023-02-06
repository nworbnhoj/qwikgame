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
                        empty($details['admin1_code']) ? $details['admin1'] : $details['admin1_code'],
                        $details['country_iso']
                    );
                    try {
                        $this->venue = new Venue($vid, TRUE);
                        if($this->venue->ok()){
                            $this->req('vid', $vid);
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
            $venue = $this->venue;
            $game = $this->game;
            $games = $venue->games();
            if(!in_array($game, $games)){
                $open = $venue->openHours();
                if(empty($open)){
                    $open['Sun'] = Hours::HRS_24;
                    $open['Mon'] = Hours::HRS_24;
                    $open['Tue'] = Hours::HRS_24;
                    $open['Wed'] = Hours::HRS_24;
                    $open['Thu'] = Hours::HRS_24;
                    $open['Fri'] = Hours::HRS_24;
                    $open['Sat'] = Hours::HRS_24;
                }
                $venue->facilitySet($game, $open);
                $venue->save(TRUE);
                self::logMsg("Added ".$game." to $vid");
            }
        }
    }


    protected function loadUser($uid){
        return new Player($uid);
    }


    public function processRequest(){
        $result = parent::processRequest();
        if(!is_null($result)){ return $result; }   // request handled by parent
        
        $player = $this->player();
        $qwik = $this->req('qwik');
        $req = $this->req();
        $result = null;
        switch ($qwik) {
            case "register":
                $email = $req['email'];
                if(!isset($email)){
                    $logReq = print_r($req, true);
                    self::logMsg("failed to register player: $logReq");
                    break;
                }
                $player->email($email);
                if(!isset($req['game'])
                || !isset($req['vid'])){
                    self::logMsg("missing parameters: game vid");
                    break;
                }
                $req['parity'] = 'any';
                $req['smtwtfs'] = 16777215;
                // intentional flow thru to available
            case "available":
                $result = $this->qwikAvailable($player, $this->venue, $req);
                break;
            case 'region':
                $result = $this->qwikReckon($player, $req);
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
        $vars['hourRows']      = self::hourRows(Page::WEEKDAYS);
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
            $regionOptions = new Options($player->regions(), Options::VALUE_TEMPLATE);

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


    function qwikAvailable($player, $venue, $req){
      if(!isset($venue) || !isset($req['game'])){ return NULL; }
      $newID = $player->availableAdd(
        $req['game'],
        $venue->id(),
        isset($req['parity']) ? $req['parity'] : 'any',
        $venue->tz(),
        isset($req['smtwtfs']) ? $req['smtwtfs'] : FALSE,
        $req
      );
      $venue->addPlayer($player->id());
      $venue->save(TRUE);
      return $newID;
    }


    function qwikReckon($player, $request){
        if(isset($request['game'])
            && isset($request['parity'])
            && isset($request['region'])){
                $player->reckonAdd($request['game'], $request['parity'], $request['region']);
        }
    }


    function qwikDelete($player, $request){
        $player->deleteData($request['id']);
    }


/////////////////////////////////////////////////////////////////////////////////////

    
}

?>
