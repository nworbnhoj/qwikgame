<?php
header('Content-Type: application/json');

require_once 'up.php';
require_once PATH_CLASS.'Qwik.php';
require_once PATH_CLASS.'Locate.php';
require_once PATH_CLASS.'Venue.php';
require_once PATH_CLASS.'PhraseBook.php';


/******************************************************************************
 * Mark provides an interface to the construction, caching and retrieval of
 * information requested by JSON to display Markers on a Google.Map Object.
 * 
 *****************************************************************************/
class Mark extends Qwik {

  static $translationFileName = "translation.xml";
  static $phraseBook;

  // key constants
  const LAT   = 'lat';
  const LNG   = 'lng';
  const NUM   = 'num';
  const INFO  = 'info';
  const AREA  = 'area';
  const NAME  = 'name';
  const NORTH = 'n';
  const EAST  = 'e';
  const WEST  = 'w';
  const SOUTH = 's';


  /***************************************************************************
   * A html template intended for a Map Marker infowindow to Mark a Venue.
   ***************************************************************************/
  const VENUE_TEMPLATE = '<div class="infowindow">
    <div class="map-mark-info-head">
      <a class="map-mark-venue" onclick="clickMapMarkVenue(event, \'[vid]\')" href="venue.php?vid=[vid]">[name]</a>
    </div>
    <div class="map-mark-info-body">
      <a class="map-mark-match" onclick="clickMapMarkMatch(event, \'[vid]\')" href="match.php?vid=[vid]&game=[game]#keen-form">{match}</a>
      <a class="map-mark-favorite" onclick="clickMapMarkFavorite(event, \'[vid]\')" href="favorite.php?vid=[vid]&game=[game]#favorite-form">{favorite}</a>
    </div>
  </div>';


  /****************************************************************************
   * A html template intended for a Map Marker infowindow to Mark a Region
   * with Venues.
   ***************************************************************************/
  const REGION_TEMPLATE = "<div class='infowindow'>
    <b>[name]</b><br>[count] {venues}
  </div>";


  private $game;
  private $lang;
  private $venueCount;


  public function __construct($game, $lang='en'){
    parent::__construct();
    $this->game = $game;
    $this->lang = $lang;
    $this->venueCount = Qwik::venueCount($game);
    unset($this->venueCount['all']);
  }


  /****************************************************************************
   * Provides a list of Region Marks screened by $country & $admin1
   * @param $country to screen the marks returned
   * @param $admin1 to screen the marks returned
   * @return Array of key:value pairs for a Marker infowindow
   ***************************************************************************/
  public function getRegionMarks($country=NULL, $admin1=NULL){
    if($this->count($country, $admin1) === 0){ return []; }
    $marks = [];
    foreach($this->venueCount as $region => $count){
      $name = explode('|', $region);
      $ord = count($name);
      $n0 = $name[0];
      if($ord === 1 && !isset($country)){
        $marks[$n0] = $this->get($n0);
      } elseif($ord === 2 && !isset($admin1) && $name[1] === $country){
        $marks["$n0|$country"] = $this->get($country, $n0);
      } elseif($ord === 3 && $name[1] === $admin1 && $name[2] === $country){
        $marks["$n0|$admin1|$country"] = $this->get($country, $admin1, $n0);
      }
    }
    return $marks;
  }


  /****************************************************************************
   * Provides a list of Venue Marks screened by $county & $admin1
   * @param $country to screen the marks returned
   * @param $admin1 to screen the marks returned
   * @return Array of key:value pairs for a Marker infowindow
   ***************************************************************************/
  public function getVenueMarks($country=NULL, $admin1=NULL, $locality=NULL){
    if($this->count($country, $admin1, $locality) === 0){ return []; }
    $marks = [];
    $vids = Qwik::venues($this->game, $country, $admin1, $locality);
    foreach($vids as $vid){
      $name = explode('|', $vid);
      $marks[$vid] = $this->get($name[3], $name[2], $name[1], $name[0]);
    }
    return $marks;
  }


  /****************************************************************************
   * Surveys all Venues and caches Marks for each Venue and each Region
   * defined by country, admin1|country & locality|admin1|country.
   ***************************************************************************/
  public function survey(){
    foreach($this->venueCount as $region => $count){
      $name = explode('|', $region);
      $n0 = $name[0];
      switch (count($name)){
        case 1:
          $key = "$n0";
          $mark = regionMark($count, $n0);
          break;
        case 2:
          $key = "$n0|$country";
          $mark = regionMark($count, $country, $n0);
          break;
        case 3:
          $key = "$n0|$admin1|$country";
          $mark = regionMark($count, $country, $admin1, $n0);
          break;
        default:
      }
      $this->save($key, $mark);
    }

    foreach(Qwik::venues() as $vid){
      $mark = venueMark($vid);
      $this->save($vid, $mark);
    }
  }


  /****************************************************************************
   * The number of Venues in locality|admin1|country
   * @param $key
   * @return Integer number of Venues in locality|admin1|country
   ***************************************************************************/
  public function count($country=NULL, $admin1=NULL, $locality=NULL){
    if ($country === null){
      return $this->venueCount[$this->game];
    } else {
      $key = $this->key($country, $admin1, $locality);
      return isset($this->venueCount[$key]) ? $this->venueCount[$key] : 0 ;
    }
  }


/*****************************************************************************/
/*** PRIVATE FUNCTIONS *******************************************************/
/*****************************************************************************/


  /****************************************************************************
   * retrieve a Mark from a json encoded file in mark/game/lang/
   * @param $key
   ***************************************************************************/
  private function retrieve($key){
    try {
      $game = $this->game;
      $lang = $this->lang;
      $path = PATH_MARK."$game/$lang/";
      $fileName = "$key.json";
      $json = self::readFile($path, $fileName);
    } catch (RuntimeException $e){
      self::logThrown($e);
//      throw new RuntimeException("failed to retrieve Mark: $fileName");
    }
    return json_decode($json);
  }


  /****************************************************************************
   * save a Mark to a json encoded file in mark/game/lang/
   ***************************************************************************/
  private function save($key, $mark){
    $json = json_encode($mark);
      $game = $this->game;
      $lang = $this->lang;
      $path = PATH_MARK."$game/$lang/";
    $fileName = "$key.json";
    if (!self::writeFile($json, $path, $fileName)){
//      throw new RuntimeException("failed to save Mark: $fileName");
      return FALSE;
    }
    return TRUE;
  }


  private function key($country=NULL, $admin1=NULL, $locality=NULL, $name=NULL){
    $key = NULL;
    if(isset($country)){
      if(isset($admin1)){
        if(isset($locality)){
          if(isset($name)){
            $key = "$name|$locality|$admin1|$country";
          } else {
            $key = "$locality|$admin1|$country";
          }
        } else {
          $key = "$admin1|$country";
        }
      } else {
        $key = "$country";
      }
    } else {
      $key = $this->game;
    }
    return $key;
  }


  private function get($country=NULL, $admin1=NULL, $locality=NULL, $name=NULL){
    $mark = FALSE;
    $key = $this->key($country, $admin1, $locality, $name);
    $game = $this->game;
    $lang = $this->lang;
    if (file_exists(PATH_MARK."$game/$lang/$key.json")){
      $mark = $this->retrieve($key);
    }
    if(!$mark){
      if(isset($name)){
        $mark = $this->venueMark($key);
      } else {
        $count = $this->venueCount[$key];
        $mark = $this->regionMark($count, $country, $admin1, $locality);
      }
      $this->save($key, $mark);
    }
    return $mark;
  }


  private function regionMark($count, $country, $admin1=NULL, $locality=NULL){
    $geometry = Locate::getGeometry($country, $admin1, $locality);
    if(!isset($geometry->location)){ return array(); }

    $coords = $geometry->location;
    $name = isset($locality) ? $locality : (isset($admin1) ? $admin1 : $country);
    $vars = [
      '[name]'=>$name,
      '[count]'=>$count
    ];
    $html = $this->html(self::REGION_TEMPLATE, $this->lang, $vars);
    $mark = array(
      self::LAT => (string) $coords->lat,
      self::LNG => (string) $coords->lng,
      self::NUM => $count,
      self::INFO => $html,
      self::NAME => $name,
      self::NORTH => (float)$geometry->viewport->northeast->lat,
      self::EAST => (float)$geometry->viewport->northeast->lng,
      self::WEST => (float)$geometry->viewport->southwest->lng,
      self::SOUTH => (float)$geometry->viewport->southwest->lat
    );
    return $mark;
  }


  private function venueMark($vid){
    $venue = new Venue($vid);
    if(!$venue || !$venue->ok()){
      Qwik::logMsg("failed to retrieve venue $vid");
      return;
    }
    $name = $venue->name();
    $vars = [
      '[vid]'=>$vid,
      '[name]'=>$name,
      '[game]'=>$this->game
    ];
    $html = $this->html(self::VENUE_TEMPLATE, $this->lang, $vars);
    $mark = array(
      self::LAT => $venue->lat(),
      self::LNG => $venue->lng(),
      self::NUM => $venue->playerCount(),
      self::INFO => $html,
      self::NAME => $name
    );
    return $mark;
  }
  
  
  function html($template, $lang, $vars){
    $search = array_keys($vars);
    $replace = array_values($vars);    
    $html = str_replace($search, $replace, $template);
    return $this->translate($html, $lang);  
  }
  
  
  function translate($html, $lang, $fb='en'){  
    if(!isset(self::$phraseBook)){
      self::$phraseBook = new PhraseBook(self::$translationFileName);
    }
    return self::$phraseBook->translate($html, $lang, $fb);  
  }

}


?>

