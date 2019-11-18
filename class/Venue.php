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


    public function save(){
        $PATH = self::PATH_VENUE;
        $fileName = $this->fileName();
        $result = self::writeXML($this->xml, $PATH, $fileName);
        if ($result){
            $games = $this->xml->xpath('game');
            foreach($games as $game){
                if(!file_exists("$PATH/$game/$fileName")){
                    if(file_exists("$PATH/$game")
                    && chdir("$PATH/$game")){
                        symlink("../$fileName", $fileName);
                        chdir("../..");
                    } else {
                       self::logMsg("Unable to create symlink for $game/$fileName");
        	           throw new RuntimeException("Unable to create symlink for $game/$fileName");
                    }
                }
            }
        } else {
        	throw new RuntimeException();
        }
        return $result;
    }


    private function retrieve(){
        $fileName = $this->fileName();
        $xml = self::readXML(self::PATH_VENUE, $fileName);
        return $xml!=NULL ? $xml : new SimpleXMLElement("<venue/>");
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


    // may introduce inconsistent results under hi multi-user load
    private function rename($newID){
        $oldID = (string) $this->xml['id'];
        if($newID != $oldID){
            $this->xml['id'] = $newID;

            // save the venue and game symlinks under the newID
            $this->save();

            $PATH = self::PATH_VENUE;
            $XML = self::XML;
            $oldFile = "$PATH/$oldID$XML";
            $newFile = "$PATH/$newID$XML";

            // temporarily replace oldfile with a symlink
            self::deleteFile($preFile);    // ?is this necessary?
            symlink($newFile, $preFile);

            // rename the venue in all player records
            $pids = $this->xml->xpath('player');
            foreach($pids as $pid){
                $player = new Player($pid);
                $player->venueRename($preID, $newID);
                $player->save();
            }

            // remove the game symlinks to the oldID
            $games = $this->xml->xpath('game');
            foreach($games as $game){
                self::deleteFile("$PATH/$game/$oldID$XML");
            }

            // delete temp symlink
            self::deleteFile($preFile);
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


    //scans and removes edits over 1 week old
    public function concludeReverts(){
        return;

        $edits = $this->xml->xpath('edit');
        foreach($edits as $edit){
            $date = $this->dateTime($edit->date['date']);
            if ($date > strtotime('+1 week')){
                self::removeElement($edit);
            }
        }
        $this->save();
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

