<?php


require_once 'Qwik.php';

class Venue extends Qwik {

    const REVERT_CHAR = '⟲';


    static function exists($id){
        $PATH = self::PATH_VENUE;
        $NAME = self::nameFile($id);
        return file_exists("$PATH/$NAME");
    }

 
    static function nameFile($id){
    	return htmlspecialchars_decode($id, ENT_HTML5) . self::XML;
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
            if ($forge){
                $this->save();
                self::logMsg("new Venue: $id");
            }
        }
    }


    private function newXML(){
        $id = $this->id();
        $field =  explode('|', $id);
        $record = "<venue ";

        if(count($field) > 0){
                $f = trim(array_shift($field));
                $record .= " name='$f'";
        }

        if(count($field) > 0){
                $f = trim(array_shift($field));
                $record .= " locality='$f'";
        }

        if(count($field) > 0){
                $f = trim(array_shift($field));
                $record .= " admin1='$f'";
        }

        if(count($field) > 0){
                $f = trim(array_shift($field));
                $record .= " country='$f'";
        }

        $record .= " />";

        return new SimpleXMLElement($record);
    }

  
    public function fileName(){
        return self::nameFile($this->id());
    }


    /**
    * Saves the Venue records to a file named id.xml and ennsures that there is a
    * symlink back to id.xml from each game directory.
    * @return TRUE if the venue and game symlinks are saved successfully, and FALSE
    * otherwise.
    * @throws RuntimeException if the venue is not saved cleanly.
    */
    public function save(){
        $PATH = self::PATH_VENUE;
        $fileName = $this->fileName();
        if (!self::writeXML($this->xml, $PATH, $fileName)){
        	throw new RuntimeException("failed to save venue $fileName");
            return FALSE;
        }
        if(!$this->saveGames($PATH, $fileName)){
            throw new RuntimeException("failed to save games for venue $fileName");
            return FALSE;
        }
        return TRUE;
    }


    /**
    * Check that each game sub-directory has a symlink back to this Venue - and if not
    * then create one.
    * @return TRUE if all game symlinks are present and accounted for, and FALSE
    * otherwise.
    * @throws RuntimeException if the linking operation fails in any way.
    */
    private function saveGames($PATH, $fileName){
    	$result = TRUE;
        $games = $this->xml->xpath('game');
        foreach($games as $game){
            if(!file_exists("$PATH/$game/$fileName")){
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
        if(!file_exists("$path/$game")){
        	if (!mkdir("$path/$game", 0660, true)){
        		throw new RuntimeException("failed to create $path/$game");
        		return FALSE;
        	}
        }        
        if (!chdir("$path/$game")){
            throw new RuntimeException("failed to change working directory to $path/$game");
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
            $xml = self::readXML(self::PATH_VENUE, $fileName);
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


    /*******************************************************************************
    Returns the Name & Suburb of a Venue as a human convenient reference.

    $vid    String    Venue ID
    *******************************************************************************/
    static function svid($vid){
        $address = explode('|', $vid);
        $name = isset($address[0]) ? $address[0] : '';
        $place = isset($address[1]) ? $address[1] : '';
        return "$name | $place";
    }


    public function lat(){
        return isset($this->xml['lat']) ? $this->xml['lat'] : '';
    }


    public function lng(){
        return isset($this->xml['lng']) ? $this->xml['lng'] : '';
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
        return "$name|$locality|$state|$country";
    }


    /**
    * Renames this Venue to $newID, and aso renames all references in Player files.
    * The general approach is to:
    * 1. change the Venue->id and save as newID.xml file
    * 2. replace the oldID.xml file with a temporary symlink to the newID.xml file
    * 3. rename all Player references to oldID with new ID
    * 4. delete all symlinks from game directories back to the oldID file
    * 5. delete the oldID.xml file
    * WARNING: may introduce inconsistent results under hi multi-user load.
    * @return True if the Venue is renamed successfully, and False if there is any problems.
    * @throws RuntimeException if there is any problem renaming the Venue.
    */
    private function rename($newID){
        $oldID = (string) $this->xml['id'];
        if($newID === $oldID){
        	return TRUE; // nothing to do
        }

        $this->xml['id'] = $newID;

        // save the venue and game symlinks under the newID
        if(!$this->save()){
            throw new RuntimeException("failed to resave venue $oldID as $newID");
            return FALSE;
        }

        $PATH = self::PATH_VENUE;
        $XML = self::XML;
        $oldFile = "$PATH/$oldID$XML";
        $newFile = "$PATH/$newID$XML";
        $oldFileTmp = "$oldFile.tmp";

        // temporarily replace oldfile with a symlink to the newFile
        // race risk here between renaming and creating symlink
        // note: maybe wiser to use single step "exec('ln -sf source dest')"
        if (!rename($oldFile, $oldFileTmp)){
            throw new RuntimeException("failed to rename venue $oldFile as $oldFileTmp");
            return FALSE;        	
        }	
        if (!symlink($newFile, $oldFile)){
            throw new RuntimeException("failed to create symlink from venue $oldFile to $newFile");
            // RECOVER FROM EXCEPTION: attempt to reinstate $oldID 
            if (!rename($oldFileTmp, $oldFile)){
                throw new RuntimeException("WARNING: INTEGRITY: failed to reinstate venue $oldFile");      	
            }
            return FALSE;        	
        } else {
            self::deleteFile($oldFileTmp);
        }

        // rename the venue in all player records
        // note: race risk here of alterations to Player.xml before the venue rename
        // note: more efficient for the Venue to store a list of all Players
        // who have ever used the venue.
        $deleteSymlink = TRUE;
        $pids = $this->xml->xpath('player');
        foreach($pids as $pid){
        	try {
                $player = new Player($pid);
                $changed = $player->venueRename($oldID, $newID);
                if ($changed && !$player->save()){
            	    self::logMsg("WARNING: failed to rename Venue($oldID) in Player($id) to Venue($newID). A temporary Symlink from $oldID to $newID is in place to preserve operation (but should be deleted when this issue is resolved for all Players with a reference to $oldID)");
            	    $deleteSymlink = FALSE;
                }
            } catch (RuntimeException $e){
            	self::logThrown($e);
            	self::logMsg("Failed to inspect Player $pid to rename Venue($oldID) to Venue($newID)");
            }
        }

        // remove the game symlinks to the oldID
        $games = $this->xml->xpath('game');
        foreach($games as $game){
        	// failure will result in broken links
            self::deleteFile("$PATH/$game/$oldID$XML");
        }

        if ($deleteSymLink){
            // delete temp symlink (rubbish but not integrity issue on failure)
        	self::deleteFile($oldFile);
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
                $edit->addChild('key', $key);
                $edit->addChild('val', $oldVal);
            }
            $this->xml[$key] = $value;
            return true;
        }
        return false;
    }


    public function addPlayer($pid){
        if (count($this->xml->xpath("/venue[player='$pid']")) == 0){
            $this->xml->addChild('player', "$pid");
        }
    }


    public function addGame($game){
        if(count($this->xml->xpath("/venue[game='$game']")) == 0){
            $this->xml->addChild('game', $game);
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

        if(!$this->save()){
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
            $set .= "\t<button class='revert' id='#venue-$edit->key' val='$edit->val'>";
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

}
?>

