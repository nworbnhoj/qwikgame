<?php


class Venue {
    const PATH_VENUE  = 'venue';
    const XML = ".xml";


    private $id;
    private $xml;
    private $log;

    public function __construct($id, $log, $forge=FALSE){
        $this->log = $log;
        $path = self::PATH_VENUE;
        $ext = self::XML;
        if(!file_exists("$path/$id$ext") && $forge){
            $this->xml = $this->newXML($id);
            $this->save();
	        $this->logMsg("login: new venue $id");
        }
        $this->xml = $this->readXML($id);
        $this->id = $this->xml['id'];
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




    public function save(){
        $cwd = getcwd();
        $path = self::PATH_VENUE;
        if(chdir($path)){
            $vid = $this->id();
            $ext = self::XML;
            $fileName = "$vid$ext";
            $this->xml->saveXML($fileName);
            $games = $this->xml->xpath('game');
            foreach($games as $game){
                if(!file_exists("$game/$filename")){
                    if(file_exists($game) && chdir($game)){
                        symlink("../$filename", $filename);
                        chdir("..");
                    } else {
                       logMsg("Unable to create symlink for $game/$filename");
                    }
                }
            }
        }

        if(!chdir($cwd)){
            $this->logMsg("unable to change working directory to $cwd");
        }
    }


    private function readXML($id){
        $path = self::PATH_VENUE;
        $ext = self::XML;
        $fileName = "$id$ext";
        if (!file_exists("$path/$fileName")) {
            $this->logMsg("unable to read venue XML $id");
            return null;
        }

        $cwd = getcwd();
        if(!chdir($path)){
            $this->logMsg("unable to change working directory to $path");
            return null;
        }

        $xml = simpleXML_load_file($fileName);
        if(!chdir($cwd)){
            $this->logMsg("unable to change working directory to $cwd");
        }
        return $xml;
    }


    private function logMsg($msg){
        $this->log->lwrite($msg);
        $this->log->lclose();
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
            deleteFile("$path/$preID$ext");
            symlink("$path/$newID$ext", "$path/$preID$ext");

            $pids = $this->xml->xpath('player');
            foreach($pids as $pid){
                $player = new Player($pid, $log);
                $player->venueRename($preID, $newID);
                $player->save();
            }

            $games = $this->xml->xpath('game');
            foreach($games as $game){
                symlink("$path/$newID$ext", "$path/$game/$newID$ext");
                deleteFile("$path/$game/$preID$ext");
            }
            deleteFile("$path/$preID$ext");    // delete temp symlink
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
                    $edit->addAttribute('id', newID());
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
        return tzDateTime($str, $this->tz());
    }


    //scans and removes edits over 1 week old
    public function concludeReverts(){
        return;

        $edits = $this->xml->xpath('edit');
        foreach($edits as $edit){
            $date = $this->dateTime($edit->date['date']);
            if ($date > strtotime('+1 week')){
                removeElement($edit);
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
            removeElement($element);
        }
        $vid = $this->id();
        deleteFile("venue/$game/$vid.xml");
    }




}


