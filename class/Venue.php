<?php


require_once 'Qwik.php';

class Venue extends Qwik {

    const REVERT_CHAR = '⟲';
    const DEFAULT_VENUE_XML = "<?xml version='1.0' encoding='UTF-8'?><venue/>";


    static function exists($id){
        $NAME = self::file4id($id);
        return file_exists(PATH_VENUE."$NAME");
    }


    static function modTime($id){
        $FILENAME = PATH_VENUE.self::file4id($id);
        return file_exists($FILENAME) ? filemtime($FILENAME) : NAN ;
    }

 
    static function file4id($id){
    	return htmlspecialchars_decode($id, ENT_HTML5) . self::XML;
    }

 
    static function id4file($file){
        $name = substr($file, 0, strlen($file) - strlen(self::XML)); //remove .xml
    	return htmlspecialchars($name, ENT_HTML5);
    }


    static function refreshID($vid){
        $fileName = self::file4id($vid);
        $venueFile = PATH_VENUE."$fileName";
        if (is_link($venueFile)){
            try {
                $target = readlink($venueFile);
                $vid = self::id4file($target);
            } catch (Exception $e){  // can throw a warning if not exist or non-symlink
                self::logThrown($e);
            }
        }
        return $vid;
    }


    /*******************************************************************************
    /* Creates a short recognisable venue identifier directly from a full unique vid.
    /* 
    /* @return name    String    name | suburb
    *******************************************************************************/
    static function svid($vid){
        $address = explode('|', $vid);
        $name = isset($address[0]) ? $address[0] : '';
        $place = isset($address[1]) ? $address[1] : '';
        return "$name | $place";
    }


    private $id;
    private $xml;

    /**
    * @throws RuntimeException if construction fails.
    */
    public function __construct($id, $forge=FALSE){
        parent::__construct();
        $this->id = $id;
        if (self::exists($id)){
            $this->xml = $this->retrieve();
        } else {
            $this->xml = $this->newXML();
            if ($this->ok() && $forge){
                $this->save();
                self::logMsg("new Venue: $id");
            }           
        }
    }


    private function newXML(){
      $xml = new SimpleXMLElement(self::DEFAULT_VENUE_XML);    
      $id = $this->id();
      $field = explode('|', $id);
      if (count($field) === 4){
        $xml->addAttribute("name",     $field[0]);
        $xml->addAttribute("locality", $field[1]);
        $xml->addAttribute("admin1",   $field[2]);
        $xml->addAttribute("country",  $field[3]);      
      } else {
        Qwik::logMsg("Warning: unable to initialize venue - invalid venueId '$id'");
        $xml = null;
      }
      return $xml;
    }

  
    public function fileName(){
        return self::file4id($this->id());
    }


    /**
    * Saves the Venue records to a file named id.xml and ennsures that there is a
    * symlink back to id.xml from each game directory.
    * @return TRUE if the venue and game symlinks are saved successfully, and FALSE
    * otherwise.
    * @throws RuntimeException if the venue is not saved cleanly.
    */
    public function save($overwrite=FALSE){
        $fileName = $this->fileName();
        if(file_exists(PATH_VENUE."$fileName") && !$overwrite){
        	throw new RuntimeException("failed to save venue $fileName - already exists");
            return FALSE;
        }
        if (!self::writeXML($this->xml, PATH_VENUE, $fileName)){
        	throw new RuntimeException("failed to save venue $fileName");
            return FALSE;
        }
        if(!$this->saveGames(PATH_VENUE, $fileName)){
            throw new RuntimeException("failed to save games for venue $fileName");
            return FALSE;
        }
        return TRUE;
    }


    /**
    * Check that each game sub-directory has a symlink back to this Venue - and if not
    * then create one.s
    * @return TRUE if all game symlinks are present and accounted for, and FALSE
    * otherwise.
    * @throws RuntimeException if the linking operation fails in any way.
    */
    private function saveGames($PATH, $fileName){
    	$result = TRUE;
        $games = $this->xml->xpath('game');
        foreach($games as $game){
            if(!file_exists("$PATH$game/$fileName")){
            	if (!$this->linkGame($PATH, $game, $fileName)){
                    throw new RuntimeException("failed to add $games for venue $fileName");
                    $result = FALSE;
            	}
            }
        }
        return $result;
    }


    /**
    * Create a symlink in the game directory back up to this venue fileName.
    * @return TRUE if the symlink is successfully created, and FALSE otherwise.
    * @throws RuntimeException if the linking operation fails in any way.
    */
    private function linkGame($path, $game, $fileName){
        $cwd = getcwd();
        if(!file_exists("$path$game")){
        	if (!mkdir("$path$game", 0755, true)){
        		throw new RuntimeException("failed to create $path$game");
        		return FALSE;
        	}
        }        
        if (!chdir("$path$game")){
            throw new RuntimeException("failed to change working directory to $path$game");
            return FALSE;
        }        
        if (!symlink("../$fileName", $fileName)){
            throw new RuntimeException("failed to create symlink for $game/$fileName");
            return FALSE;
        }
        if(!chdir("$cwd")){
            throw new RuntimeException("failed to return working directory to $cwd");
            return FALSE;
        }
        return TRUE;
    }


    /**
    * @throws RuntimeException if the xml cannot be read from file.
    */
    private function retrieve(){
    	try {
            $fileName = $this->fileName();
            $xml = self::readXML(PATH_VENUE, $fileName);
        } catch (RuntimeException $e){
        	self::logThrown($e);
        	$xml = new SimpleXMLElement("<venue/>");
        	$id = $this->id;
        	throw new RuntimeException("failed to retrieve Venue: $id");
        }
        return $xml;
    }


    public function ok(){
        return !is_null($this->xml);
    }


    public function address(){
        return isset($this->xml['address']) ? $this->xml['address'] : '';
    }


    public function country(){
        return isset($this->xml['country']) ? $this->xml['country'] : '';
    }


    public function id(){
        return $this->id;
    }


    public function lat(){
        return isset($this->xml['lat']) ? (string) $this->xml['lat'] : '';
    }


    public function lng(){
        return isset($this->xml['lng']) ? (string) $this->xml['lng'] : '';
    }


    public function manager(){
        $mid = (string) $this->xml->xpath('manager');
        return isset($mid) ? new Manager($mid) : NULL;
    }


    public function name(){
        return $this->xml['name'];
    }


    public function note(){
        return isset($this->xml['note']) ? (string) $this->xml['note'] : '';
    }


    public function playerCount(){
        return count($this->xml->xpath('player'));
    }


    public function players(){
        $players = array();
        $xmlPlayers = $this->xml->xpath('player');
        foreach($xmlPlayers as $xmlPlayer){
            $players[] = (string) $xmlPlayer;
        }
        return $players;
    }


    public function phone(){
        return isset($this->xml['phone']) ? (string) $this->xml['phone'] : '';
    }


    public function placeid(){
        return isset($this->xml['placeid']) ? (string) $this->xml['placeid'] : '';
    }


    public function admin1(){
        return isset($this->xml['admin1']) ? (string) $this->xml['admin1'] : '';
    }


    public function strNum(){
        return isset($this->xml['str-num']) ? (string) $this->xml['str-num'] : '';
    }


    public function route(){
        return isset($this->xml['route']) ? (string) $this->xml['route'] : '';
    }


    public function locality(){
        return isset($this->xml['locality']) ? (string) $this->xml['locality'] : '';
    }


    public function tz(){
        return isset($this->xml['tz']) ? (string) $this->xml['tz'] : '';
    }


    public function url(){
        return isset($this->xml['url']) ? (string) $this->xml['url'] : '';
    }


    public function facility($game){
        return $this->xml->xpath("facility[game='$game'");
    }


    public function games(){
        $games = array();
        $elements = $this->xml->xpath("//game");

        foreach($elements as $element){
            $games[] = (string) $element;
        }
        return $games;
    }


    /**
    * Updates the Venue ID when there is a change to the Venue Name, Locality, 
    * Admin1 or Country.
    * @throws RuntimeException if there is a problem saving the Venue with the new ID
    */
    public function updateID(){
        $xml = $this->xml;
        $name = $xml['name'];
        $locality = $xml['locality'];
        $state = isset($xml['admin1_code'])
            ? $xml['admin1_code']
            : $xml['admin1'];
        $country = $xml['country'];

        $vid = self::venueID($name, $locality, $state, $country);
        $this->rename($vid);
    }


    static public function venueID($name, $locality, $state, $country){
        $state =  empty($state) ? $country : $state ;
        return "$name|$locality|$state|$country";
    }


    /**
    * Renames this Venue to $newID, and aso renames all references in Player files.
    * The general approach is to:
    * 1. change the Venue->id and save as newID.xml file
    * 2. replace the oldID.xml file with a symlink to the newID.xml file
    * 3. rename Player Favourite & Match references to oldID with newID
    * 4. delete all symlinks from game directories back to the oldID file
    * WARNING: may introduce inconsistent results under hi multi-user load.
    * @return True if the Venue is renamed successfully, and False if there is any problems.
    * @throws RuntimeException if there is any problem renaming the Venue.
    */
    private function rename($newID){
        $oldID = $this->id;
        if($newID === $oldID){
            return TRUE; // nothing to do
        }

        $PATH = PATH_VENUE;
        $XML = self::XML;

        $oldName = "$oldID$XML";
        $oldFile = PATH_VENUE."$oldName";
        $oldFileTmp = "$oldFile.tmp";

        $newName = "$newID$XML";
        $newFile = PATH_VENUE."$newName";

        // save the venue and game symlinks under the newID
        $this->id = $newID;

        // remove an existing $newFile iff it is a symlink to $oldFile
        if (is_link($newFile)
        && strcmp(readlink($newFile), $oldName) === 0){
            unlink($newFile);
        }

        if(!$this->save(FALSE)){
            $this->id = $oldID;
            throw new RuntimeException("failed to resave venue $oldID as $newID");
            return FALSE;
        }


        // create a symlink from the oldfile to the newFile
        // race risk here between renaming and creating symlink
        // note: maybe wiser to use single step "exec('ln -sf source dest')"
        if (!rename($oldFile, $oldFileTmp)){
            throw new RuntimeException("failed to rename venue $oldFile as $oldFileTmp");
            return FALSE;        	
        }	
        if (!symlink($newName, $oldFile)){
            throw new RuntimeException("failed to create symlink from venue $oldFile to $newName");
            // RECOVER FROM EXCEPTION: attempt to reinstate $oldID 
            if (!rename($oldFileTmp, $oldFile)){
                throw new RuntimeException("WARNING: INTEGRITY: failed to reinstate venue $oldFile");      	
            }
            return FALSE;        	
        } else {
            self::deleteFile($oldFileTmp);
        }

        // rename the venue in all Players with this Venue as a Favourite.
        // Other Player Matches at this Venue are refreshed in Match->construct() on-the-fly
        $pids = $this->xml->xpath('player');
        foreach($pids as $pid){
            try {
                $player = new Player($pid);
                $changed = $player->venueRename($oldID, $newID);
                if ($changed && !$player->save()){
            	    self::logMsg("Failed to rename Venue($oldID) in Player($id) to Venue($newID).");
                }
            } catch (RuntimeException $e){
            	self::logThrown($e);
            	self::logMsg("Failed to inspect Player $pid to rename Venue($oldID) to Venue($newID)");
            }
        }

        // remove the game symlinks to the oldID
        $games = $this->xml->xpath('game');
        foreach($games as $game){
            try {
                self::deleteFile(PATH_VENUE."$game/$oldName");
            } catch (RuntimeException $e){
                // may fail because link does not exist (no problem anymore)
                // or because link is not writable (results in a broken link).
                self::logThrown($e);
                self::logMsg("Failed to remove $game game link to old venueID $oldName");
            }
        }
    }


    public function updateAtt($key, $value){
        if (empty($key)){
            return false;
        }

        $datetime = $this->dateTime('now');
        $date = $datetime->format('d-m-y H:i');
        $oldVal = $this->xml[$key];
        if ($oldVal != $value){
            if ( strlen(trim($oldVal)) > 0){
                $edit = $this->xml->addChild('edit', '');
                $edit->addAttribute('date', $date);
                $edit->addAttribute('id', self::newID());
                $edit->addChild('key', htmlspecialchars($key));
                $edit->addChild('val', htmlspecialchars($oldVal));
            }
            $this->xml[$key] = $value;
            return true;
        }
        return false;
    }


    const FURNISH = array('phone', 'url','tz','lat','lng','address','str-num','route');

    public function furnish($details){
      foreach(self::FURNISH as $key){
        if(isset($details[$key])){
          $this->updateAtt($key, $details[$key]);
        }
      }
      $this->save(TRUE);
    }


    public function setManager($mid){
        $this->updateAtt('manager', $mid);
    }


    public function addPlayer($pid){
        if (count($this->xml->xpath("/venue[player='$pid']")) == 0){
            $this->xml->addChild('player', htmlspecialchars("$pid"));
        }
    }


    public function addGame($game){
        if(count($this->xml->xpath("/venue[game='$game']")) == 0){
            $this->xml->addChild('game', htmlspecialchars($game));
            return true;
        }
        return false;
    }


    public function playerIDs(){
        $players = array();
        $playerElements = $this->xml->xpath('player');
        foreach ($playerElements as $element){
            $players[] = (string) $element;
        }
        return $players;
    }


    /********************************************************************************
    Returns a new DateTime object for a time at the $venue requested

    $str    String    A time & date
    $venue    XML        venue data
    ********************************************************************************/
    public function dateTime($str='now'){
    //echo "<br>VENUEDATETIME $str</br>" . $venue['tz'];
        return self::tzDateTime($str, $this->tz());
    }


    /**
    * scans and removes edits over 1 week old
    * @throws RuntimeException if there is some problem saving the venue after concluding reverts.
    */
    public function concludeReverts(){
        return;  //@todo finish implimentation of function concludeReverts

        $edits = $this->xml->xpath('edit');
        foreach($edits as $edit){
            $date = $this->dateTime($edit->date['date']);
            if ($date > strtotime('+1 week')){
                self::removeElement($edit);
            }
        }

        if(!$this->save(TRUE)){
        	$id = $this->id();
            throw new RuntimeException("failed to conclude reverts for venue $id");
            return FALSE;
        }
    }


    public function revertSet(){
        $edits = $this->xml->xpath('edit');
        if (count($edits) == 0){
            return '';
        }

        $set = "<fieldset id='edit-revert-div' class='middle'>\n";
        $set .= "\t<legend>Click to revert a prior edit.</legend>\n";
        foreach($edits as $edit){
            $revertID = $edit['id'];
            $set .= "\t<button class='revert' id='venue-$edit->key' val='$edit->val'>";
            $set .= "\t\t".self::REVERT_CHAR." <s>$edit->val</s>\n";
            $set .= "\t</button>\n";
        }
        $set .= "\t<br>\n";
        $set .= "</fieldset>\n";
        return $set;
    }
    
    
    
    function venueRemoveGame($game){
        $elements = $this->xml->xpath("/venue[game='$game']");

        foreach($elements as $element){
            self::removeElement($element);
        }
        $vid = $this->id();
        self::deleteFile("venue/$game/$vid.xml");
    }


    public function facilitySet($game, $days){
        $element = $this->facility($game);
        if (!isset($element)){
            $newID = self::newID();
            $element = $this->xml->addChild('facility', '');
            $element->addAttribute('id', $newID);
            $element->addAttribute('game', $game);
        }
        foreach($days as $day => $hrs){
            $e = $element->xpath("hrs[day='$day']");
            if(isset($e)){
                $e = htmlspecialchars($hrs);
            } else {
                $e = $element->addChild('hrs', htmlspecialchars($hrs));
                $e->addAttribute('day', $day);
            }
        }
        return $element['id'];
    }


    public function facilityHours($game, $datetime){
        $available = 0;
        $element = $this->facility($game);
        if(isset($element)) {
            $dayYmd = $dateTime->format('Y-m-d');
            $available = $element->xpath("hrs[day='$dayYmd']");
            if(!isset($available)){
                $dayD = $dateTime->format('D');
                $available = $element->xpath("hrs[day='$dayD']");
                if(!isset($available)){
                    $available = 0;
                }
            }
        }
        return $available;
    }

}
?>

