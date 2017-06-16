<?php


class Ranking {

    const PATH = 'uploads';
    const EXT = '.csv';
    const RANK_PARITY = array(128=>-2, 64=>-2, 32=>-1, 16=>-1, 8=>0, 4=>0, 2=>0, 1=>0, -1=>0, -2=>0, -4=>0, -8=>0, -16=>1, -32=>1, -64=>2, -128=>2);


    private $xml;
    public $transcript;
    public $valid;
    private $log;

    public function __construct($fileName=NULL, $game=NULL, $pid=NULL, $title=NULL){
        $this->valid = true;
        if(is_null($filename)){
            $this->processUpload($game, $pid, $title);
        } else {
            $this->readXML($fileName);
        }
    }


    private function processUpload($game, $pid, $title){
        $date = date_create();
        $tmp_name = $_FILES["filename"]["tmp_name"];
        $fileName = $game . "RankUpload" . $date->format('Y:m:d:H:i:s');
        $path = self::PATH . "/" . $fileName . self::EXT;

        $this->moveUpload($tmp_name, $path);
        $file = $this->openUpload($path);
        $this->metadata($game, $tmp_name, $pid, $title, $data, $fileName, $path);
        $this->checkHash($file);
        $this->parse($file);
        fclose($file);

        if($this->valid){
            $this->valid = $this->save();
        }

        if(!$this->valid){
            $this->transcript .= 'some weird error saving the data :-(     )';
        }
    }


    private function moveUpload($baseName, $path){
        if (move_uploaded_file($baseName, $path)) {
            $this->transcript .= "uploaded OK<br>";
        } else {
            $this->transcript .= "there was a weird error uploading your file<br>";
            $this->valid = FALSE;
        }
    }


    private function openUpload($path){
        if (!$this->valid){
            return null;
        }
        // open the uploaded file
        $file = fopen($path, "r");
        if ($file){
            $this->transcript .= "opened OK<br>";
        } else {
            $this->transcript .= "unable to open file<br>";
            $this->valid = FALSE;
        }
        return $file;
    }


    private function metadata($game, $baseName, $pid, $title,$data, $fileName, $path){
        if($this->valid){
            $this->xml = new SimpleXMLElement("<upload></upload>");
            $this->xml->addAttribute('time', $date->format('d-m-Y H:i:s'));
            $this->xml->addAttribute('player', $pid);
            $this->xml->addAttribute('uploadName', $fileToUpload);
            $this->xml->addAttribute('uploadHash', hash_file('sha256', $path));
            $this->xml->addAttribute('fileName', $fileName);
            $this->xml->addAttribute('game', $game);
            $this->xml->addAttribute('title', $title);
            $this->xml->addAttribute('status', 'uploaded');
            $this->xml->addAttribute('id', newID());
        }
    }


    private function checkHash($file){
        if($this->valid){
            $facilitatorSHA256 = hash('sha256', 'facilitator@qwikgame.org');

//            $line = SECURITYsanitizeHTML(fgets($file));
            $line = fgets($file);

            $testSHA256 = trim(explode(',', $line)[1]);

            if((strlen($testSHA256) != 64)
            || (strcmp($facilitatorSHA256, $testSHA256) != 0)){
                $this->transcript .= "facilitator@qwikgame.org hash mismatch<br>";
                $this->valid = FALSE;
            } else {
                $this->transcript .= "facilitator@qwikgame.org hash OK<br>";
            }
        }
    }



    private function parse(){
        if($this->valid){
            $lineNo = 0;
            $ranks = array();
            $rankCount = 0;
            while($this->valid && !feof($file)) {
            //                $line = SECURITYsanitizeHTML(fgets($file));
               $line = fgets($file);

                $lineNo++;
                $tupple = explode(',', $line);
                $rank = (int) trim($tupple[0]);
                $sha256 = trim($tupple[1]);
                if ($rank > 0 && $rank < 10000 && strlen($sha256) == 64){
                    $ranks[$rank] = $sha256;
                    $child = $upload->addChild('sha256', $sha256);
                    $child->addAttribute('rank', $rank);
                    $rankCount++;
                } else {
                    $this->transcript .= "data on line $lineNo ignored<br>$line";
                }
            }
            $this->transcript .= "$rankCount player rankings found<br>";
        }
    }


    private function save(){
        $cwd = getcwd();
        $path = self::PATH;
        if(chdir($path)){
            $fileName = $this->fileName();
            $ext = self::EXT;
            $this->xml->saveXML("$fileName.$ext");
            if(chdir($cwd)){
                $this->log->logMsg("unable to change working directory to $cwd");
                return false;
            }
        } else {
            logMsg("unable to change working directory to $path");
            return false;
        }
        return true;
    }


    public function readXML($id){
        $id = $this->id;
        $path = self::PATH;
        $filename = "$id.xml";
        if (!file_exists("$path/$filename")) {
            $this->logMsg("unable to read ranking XML " . snip($id));
            return null;
        }

        $cwd = getcwd();
        if(chdir($path)){
            $xml = simpleXML_load_file($filename);
            if(!chdir($cwd)){
                $this->logMsg("unable to change working directory to $cwd");
            }
            return $xml;
        } else {
            $this->logMsg("unable to change working directory to $path");
        }
    }


    private function fileName(){
        return $this->xml['fileName'];
    }


    private function id(){
        return $this->xml['id'];
    }


    public function status($status=NULL){
        if (isset($status)){
            $this->xml['status'] = $status;
        }
        return (string) $this->xml['status'];
    }



    /*******************************************************************************
    Process a User request to activate a set of uploaded player rankings.

    $ranking    XML    The uploaded rankings

    The rankings are inserted into the XML data of the ranked players
    (creating new anon players as required)

    ********************************************************************************/
    public function insert($log){
        $rankParity = self::RANK_PARITY;

        $datetime = date_create();
        $date = $datetime->format('d-m-Y');
        $rankingID = $ranking['id'];
        $game = $ranking['game'];

        $ranks = array();
        $anonIDs = $ranking->xpath("sha256");
        foreach($anonIDs as $anonID){
            $anonRank = $anonID['rank'];
            $ranks["$anonRank"] = "$anonID";
        }

        foreach($anonIDs as $anonID){
            $anonRank = (int) $anonID['rank'];

            $anon = new Player($anonID, $log);

            foreach($rankParity as $rnk => $pty){
                $rivalRank = $anonRank + (int) $rnk;
                $rivalID = $ranks["$rivalRank"];
                if (isset($rivalID)){
                    $parity = $anon->addChild('rank', '');
                    $parity->addAttribute('rely', '3.0');
                    $parity->addAttribute('id', $rankingID);
                    $parity->addAttribute('rival', $rivalID);
                    $parity->addAttribute('game', $game);
                    $parity->addAttribute('date', $date);
                    $parity->addAttribute('parity', $pty);
                }
            }
            $anon->save();
        }
        $this->status('active');
        $this->save();
    }


    /*******************************************************************************
    Process a User request to de-activate a set of uploaded player rankings.

    $ranking    XML    The uploaded rankings
    ********************************************************************************/
    public function extract($log){
        $rankingID = $this->id();
        $anonIDs = $this->xml->xpath("sha256");
        foreach($anonIDs as $anonID){
            $anon = new Player($anonID, $log);
            if ($anon){
                if(empty($anon->email())){
                    removePlayer($anonID);
                } else {
                    $ranks = $anon->xpath("rank[@id=$rankingID]");
                    foreach($ranks as $rank){
                        removeElement($rank);
                    }
                }
            }
        }
        $this->status('uploaded');
        $this->save();
    }


}
