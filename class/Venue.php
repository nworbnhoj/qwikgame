<?php


require_once 'class/Qwik.php';

class Venue extends Qwik {


    private $id;
    private $xml;

    public function __construct($id, $forge=FALSE){
        parent::__construct();
        $path = self::PATH_VENUE;
        $ext = self::XML;
        if(!file_exists("$path/$id$ext") && $forge){
            $this->xml = $this->newXML($id);
            $this->save();
	        self::logMsg("login: new venue $id");
        }
        $this->id = $this->xml['id'];
        $this->xml = $this->retrieve($this->fileName());
    }


    private function newXML($description){
        $field = explode(',', $description);
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
        $result = self::writeXML(
            $this->xml, 
            self::PATH_VENUE, 
            $this->fileName()
        );
        
        if ($result){
            $games = $this->xml->xpath('game');
            foreach($games as $game){
                if(!file_exists("$game/$filename")){
                    if(file_exists($game) && chdir($game)){
                        symlink("../$filename", $filename);
                        chdir("..");
                    } else {
                       self::logMsg("Unable to create symlink for $game/$filename");
                    }
                }
            }
        }
        return $result;
    }


    public function retrieve($fileName){
        return self::readXML( 
            self::PATH_VENUE, 
            $fileName         
        );
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


    public function phone(){
        return isset($this->xml['phone']) ? $this->xml['phone'] : '';
    }


    public function state(){
        return $this->xml['state'];
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


    public function updateVenue($update){
        $save = updateAtt('name', $update);
        $save = updateAtt('address', $update) || $save;
        $save = updateAtt('suburb', $update) || $save;
        $save = updateAtt('state', $update) || $save;
        $save = updateAtt('country', $update) || $save;
        if($save){
            $this->updateID();
        }
        $save = updateAtt('phone', $update) || $save;
        $save = updateAtt('url', $update)  || $save;
        $save = updateAtt('tz', $update) || $save;
        $save = updateAtt('note', $update) || $save;
        $save = updateAtt('lat', $update) || $save;
        $save = updateAtt('lng', $update) || $save;
        if($save){
            $this->save();
        }
    }


    private function updateID(){
        $vid = venueID(
            $this->xml['name'],
            $this->xml['address'],
            $this->xml['suburb'],
            $this->xml['state'],
            $this->xml['country']
        );
        $this->rename($vid);
    }


    public function venueID($name, $address, $suburb, $state, $country){
        return "$name|$address|$suburb|$state|$country";
    }


    // may introduce inconsistent results under hi multi-user load
    private function rename($newID){
    // echo "<br>RENAMEVENUE to $newID<br>";
        $preID = (string) $this->xml['id'];
        if($newID != $preID){
            $this->xml['id'] = $newID;
            $this->save();

            // temporarily replace oldfile with a symlink
            $path = self::VENUE_PATH;
            $ext = self::XML;
            self::deleteFile("$path/$preID$ext");
            symlink("$path/$newID$ext", "$path/$preID$ext");

            $pids = $this->xml->xpath('player');
            foreach($pids as $pid){
                $player = new Player($pid);
                $player->venueRename($preID, $newID);
                $player->save();
            }

            $games = $this->xml->xpath('game');
            foreach($games as $game){
                symlink("$path/$newID$ext", "$path/$game/$newID$ext");
                self::deleteFile("$path/$game/$preID$ext");
            }
            self::deleteFile("$path/$preID$ext");    // delete temp symlink
        }
    }





    private function updateAtt($key, $update){
    //echo "<br>updateAtt $key ";
        if (isset($update[$key])){
            $newVal = $update[$key];
            $datetime = $this->dateTime('now');
            $date = $datetime->format('d-m-y H:i');
            $oldVal = $this->xml[$key];
            if ($oldVal != $newVal){
                if ( strlen(trim($oldVal)) > 0){
                    $edit = $this->xml->addChild('edit', '');
                    $edit->addAttribute('date', $date);
                    $edit->addAttribute('id', self::newID());
                    $edit->addChild('key', $key);
                    $edit->addChild('val', $oldVal);
                }
                $this->xml[$key] = $newVal;
                return true;
            }
        }
        return false;
    }


    public function addPlayer($playerID){
        if (count($this->xml->xpath("/venue[player='$playerID']")) == 0){
            $this->xml->addChild('player', "$playerID");
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

        $div .= "<div id='edit-revert-div' class='middle'>\n";
        $div .= "\tClick to revert a prior edit.<br>\n";
        foreach($edits as $edit){
            $revertID = $edit['id'];
            $div .= "\t<button class='revert' id='#venue-$edit->key' val='$edit->val'>";
            $div .= "\t\t".REVERT_CHAR." <s>$edit->val</s>\n";
            $div .= "\t</button>\n";
        }
        $div .= "\t<br>\n";
        $div .= "</div>\n";
        return $div;
    }
    
    
    
    function venueRemoveGame($game){
        $elements = $this->xpath("/venue[game='$game']");

        foreach($elements as $element){
            self::removeElement($element);
        }
        $vid = $this->id();
        self::deleteFile("venue/$game/$vid.xml");
    }




}


