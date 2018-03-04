<?php


require_once 'Qwik.php';

class Venue extends Qwik {

    const REVERT_CHAR    = '⟲';

    private $id;
    private $xml;

    public function __construct($id, $forge=FALSE){
        parent::__construct();
        $this->id = $id;
        $path = self::PATH_VENUE;
        $fileName = $this->fileName();
        $this->xml = file_exists("$path/$fileName") ?
            $this->retrieve($fileName) :
            $this->newXML($id);
        if ($forge){
            $this->save();
            self::logMsg("new Venue: $id");
        }
    }


    private function newXML($description){
        $field =  explode('|', $description);
        if(count($field) == 1){
            $field =  explode(',', $description);
        }
        $record = "<venue ";

        if(count($field) > 0){
                $f = trim(array_shift($field));
                $record .= " name='$f'";
        }

        if(count($field) > 0){
                $f = trim(array_shift($field));
                $record .= " address='$f'";
        }

        if(count($field) > 0){
                $f = trim(array_shift($field));
                $record .= " suburb='$f'";
        }

        if(count($field) > 0){
                $f = trim(array_shift($field));
                $record .= " state='$f'";
        }

        if(count($field) > 0){
                $f = trim(array_shift($field));
                $record .= " country='$f'";
        }

        $record .= " />";

        return new SimpleXMLElement($record);
    }
    
    
  
    public function fileName(){
        return $this->id() . self::XML;
    }


    public function save(){
        $path_venue = self::PATH_VENUE;
        $fileName = $this->fileName();
        $result = self::writeXML(
            $this->xml, 
            $path_venue,
            $fileName
        );
        
        if ($result){
            $games = $this->xml->xpath('game');
            foreach($games as $game){
                if(!file_exists("$path_venue/$game/$fileName")){
                    if(file_exists("$path_venue/$game") && chdir("$path_venue/$game")){
                        symlink("../$fileName", $fileName);
                        chdir("../..");
                    } else {
                       self::logMsg("Unable to create symlink for $game/$fileName");
                    }
                }
            }
        }
        return $result;
    }


    public function retrieve($fileName){
        $xml = self::readXML(
            self::PATH_VENUE, 
            $fileName         
        );
        return $xml!=null ? $xml : new SimpleXMLElement("<venue/>");
    }


    public function exists(){
        return !is_null($this->xml);
    }


    public function address(){
        return isset($this->xml['address']) ? $this->xml['address'] : '';
    }


    public function country(){
        return $this->xml['country'];
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
        $place = isset($address[2]) ? $address[2] : '';
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
        return isset($this->xml['note']) ? $this->xml['note'] : '';
    }


    public function playerCount(){
        return count($this->xml->xpath('player'));
    }


    public function players(){
        $players = array();
        $xmlPlayers = $this->xml->xpath('player');
        foreach($xmlPlayers as $xmlPlayer){
            $players[] = "$xmlPlayer";
        }
        return $players;
    }


    public function phone(){
        return isset($this->xml['phone']) ? $this->xml['phone'] : '';
    }


    public function state(){
        return isset($this->xml['state']) ? $this->xml['state'] : '';
    }


    public function suburb(){
        return isset($this->xml['suburb']) ? $this->xml['suburb'] : '';
    }


    public function tz(){
        return isset($this->xml['tz']) ? $this->xml['tz'] : '';
    }


    public function url(){
        return isset($this->xml['url']) ? $this->xml['url'] : '';
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
        $vid = $this->venueID(
            $this->xml['name'],
            $this->xml['address'],
            $this->xml['suburb'],
            $this->xml['state'],
            $this->xml['country']
        );
        $this->rename($vid);
    }


    static public function venueID($name, $address, $suburb, $state, $country){
        return "$name|$address|$suburb|$state|$country";
    }


    // may introduce inconsistent results under hi multi-user load
    private function rename($newID){
    // echo "<br>RENAMEVENUE to $newID<br>";
        $oldID = (string) $this->xml['id'];
        if($newID != $oldID){
            $this->xml['id'] = $newID;

            // save the venue and game symlinks under the newID
            $this->save();

            $path = self::PATH_VENUE;
            $ext = self::XML;
            $oldFile = "$path/$oldID$ext";
            $newFile = "$path/$newID$ext";

            // temporarily replace oldfile with a symlink
            self::deleteFile($preFile);
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
                self::deleteFile("$path/$game/$oldID$ext");
            }

            // delete temp symlink
            self::deleteFile($preFile);
        }
    }





    public function updateAtt($key, $value){
        if (empty($key)
        || empty($update)){
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


    public function revertDiv(){
        $edits = $this->xml->xpath('edit');
        if (count($edits) == 0){
            return '';
        }

        $div = "<div id='edit-revert-div' class='middle'>\n";
        $div .= "\tClick to revert a prior edit.<br>\n";
        foreach($edits as $edit){
            $revertID = $edit['id'];
            $div .= "\t<button class='revert' id='#venue-$edit->key' val='$edit->val'>";
            $div .= "\t\t".self::REVERT_CHAR." <s>$edit->val</s>\n";
            $div .= "\t</button>\n";
        }
        $div .= "\t<br>\n";
        $div .= "</div>\n";
        return $div;
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


