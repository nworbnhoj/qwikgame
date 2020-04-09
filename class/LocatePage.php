<?php

require_once 'Page.php';
require_once 'Venue.php';
require_once 'VenuePage.php';
require_once 'Locate.php';
require_once 'SimilarVenueList.php';
require_once 'Repost.php';


class LocatePage extends Page {

    private $game;
    private $description;
    private $repost;
    private $hideAddressPrompt = 'hidden';

    public function __construct($templateName='locate'){
        parent::__construct(Html::readTemplate($templateName), $templateName);

        $this->game        = $this->req('game');
        $this->description = $this->req('venue');
        $this->repost      = $this->req('repost');
    }


    public function serve(){
        if (empty($this->game)){
            header("Location: ".QWIK_URL, TRUE, 307);
            exit;
        }
	   parent::serve();
    }
	
	
    public function processRequest(){
        $vid = NULL;
        $description = $this->description;

        // first check if the description is a vid (venue id)
        if (Venue::exists($description)){
            $vid = $description;
        }

        // Process a svid (short vid) submitted in PlayerPage
        if(empty($vid)){    // check if the description is a svid
            $vids = $this->matchShortVenueID($description, $this->game);
        $matchCount = count($vids);
        $vid = ($matchCount == 1) ? $vids[0] : NULL;
        }

        // Process a new venue from a placeid from LocatePage
        if(empty($vid)){
            $placeid = $this->req('placeid');
            if(!empty($placeid)){
                $details = Locate::getDetails($placeid);
                if($details){
                    $vid = Venue::venueID(
                        $details['name'],
                        $details['locality'],
                        $details['admin1'],
                        $details['country_iso']
                    );
                    try {
                        $venue = new Venue($vid, TRUE);
                        $venue->updateAtt('placeid', $placeid);
                        $this->furnish($venue, $details);
                    } catch (RuntimeException $e){
                        self::alert("{Oops}");
                        self::logThrown($e);
                        unset($vid);
                    }
                }
            }
        }

        // Process a new venue submitted from LocatePage
        if(empty($vid)){
            $name     = $this->req('name');
            $locality = $this->req('locality');
            $admin1   = $this->req('admin1');
            $country  = $this->req('country');
            if(isset($name)
            && isset($locality)
            && isset($admin1)
            && isset($country)){
                $vid = Venue::venueID($name, $locality, $admin1, $country);
                if (!Venue::exists($vid)){
                    try {
                        $venue = new Venue($vid, TRUE);
                        $description = "$name, $locality, $admin1";
                        $placeid = Locate::getPlace($description, $country);
                        if(isset($placeid)){
                            $this->furnish($venue, Locate::getDetails($placeid));
                        } else {
                            $tz = Locate::guessTimezone($locality, $admin1, $country);
                            $venue->updateAtt('tz', $tz);
                            $venue->save(TRUE);
                        }
                    } catch (RuntimeException $e){
                        self::alert("{Oops}");
                        self::logThrown($e);
                        unset($vid);
                    }
                }
            } else {
                self::message({prompt_complete_vid});
            }
        }

        if (isset($vid)){    // repost the query with the located $vid
            $this->req('vid', $vid);
            $query = http_build_query($this->req());
            $repost = $this->repost;
            $url = QWIK_URL."$repost?$query";
            header("Location: $url", TRUE, 307);
            exit;
        }
    }


    private function furnish($venue, $address){
        if ($venue && $address){
            $venue->updateAtt('phone',   $address['phone']);
            $venue->updateAtt('url',     $address['url']);
            $venue->updateAtt('tz',      $address['tz']);
            $venue->updateAtt('lat',     $address['lat']);
            $venue->updateAtt('lng',     $address['lng']);
            $venue->updateAtt('address', $address['formatted']);
            $venue->updateAtt('str-num', $address['street_number']);
            $venue->updateAtt('route',   $address['route']);
            $venue->save(TRUE);
        }
    }


    /*******************************************************************************
    Returns an Array of Venue ID's (vid) that match the $svid provided.

    $svid  String 	 The Short Venue ID includes only the Name & Locality of the Venue.

    The Short Venue ID $svid is a non-unique human convenient way of referring to a
    Venue. This functions finds zero or more $vid that match the $svid
    *******************************************************************************/
    function matchShortVenueID($svid, $game){
        $matchedVids = array();
        $vids = self::venues($game);
        foreach($vids as $vid){
            if($svid === Venue::svid($vid)){
                $matchedVids[] = $vid;
            }
        }
        return $matchedVids;
    }


    public function variables(){
        // resupply the prior entries if they could not be geocoded
        $name = $this->req('name');
        $locality = $this->req('locality');
        $admin1 = $this->req('admin1');
        $country = $this->req('country');
        $placeid = $this->req('placeid');
        $userCountry = Locate::geolocate('countryCode');

        if (empty($name)){
            $name = $this->description;
            $geocoded = Locate::parseAddress($this->description, $userCountry);
            if($geocoded){
                $locality = $geocoded['locality'];
                $admin1   = $geocoded['admin1'];
                $country  = $geocoded['country_iso'];
                $placeid  = $geocoded['placeid'];
            }
        }

        $vars = parent::variables();
        $vars['game']          = $this->game;
        $vars['repost']        = $this->repost;
        $vars['venueName']     = isset($name)     ? $name     : '';
        $vars['venueLocality'] = isset($locality) ? $locality : '';
        $vars['venueAdmin1']   = isset($admin1)   ? $admin1   : '';
        $vars['venueCountry']  = isset($country)  ? $country  : $userCountry;
        $vars['placeid']       = isset($placeid)  ? $placeid  : '';
        return $vars;
    }


    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();

        $similarVenueList = new SimilarVenueList($html, 'similar.venue', $this->description);
        $vars['similarVenues'] = $similarVenueList->make();

        $repost = new Repost($html, 'repost', $this->req());
        $made = $repost->make();
        $vars['repost-new'] = $made;
        $vars['repost-existing'] = $made;
        return parent::make($vars); 
    }

}

?>
