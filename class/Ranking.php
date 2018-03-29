<?php


require_once 'Qwik.php';
require_once 'Player.php';

class Ranking extends Qwik {

    const CSV = '.csv';
    const XML = '.xml';
//    const RANK_PARITY = array(128=>12.8, 64=>6.4, 32=>3.2, 16=>1.6, 8=>0.8, 4=>0.4, 2=>0.2, 1=>0.1, -1=>-0.1, -2=>-0.2, -4=>-0.4, -8=>-0.8, -16=>-1.6, -32=>-3.2, -64=>-6.4, -128=>-12.8);

    const RANK_PARITY = array(4=>0.8, 3=>0.6, 2=>0.4, 1=>0.2, -1=>-0.2, -2=>-0.4, -3=>0.6, -4=>-0.8);


    private $xml;
    public $transcript;
    public $valid;

    public function __construct($rankingID, $game=NULL, $path=NULL){
        parent::__construct();
        $this->valid = true;
        if(is_null($path)){
            $this->xml = $this->retrieve($rankingID);
        } else {
            $this->xml = new SimpleXMLElement("<upload></upload>");
            $this->xml->addAttribute('fileName', $rankingID);
            $this->processUpload($game, $path);
            if ($this->valid){
                $date = date_create();
                $this->xml->addAttribute('time', $date->format('d-m-Y H:i:s'));
                $this->xml->addAttribute('path', $path);
                $this->xml->addAttribute('game', $game);
                $this->xml->addAttribute('status', 'uploaded');
                $this->xml->addAttribute('id', self::newID());
                $this->save();
            }
        }
    }


    private function processUpload($game, $path){
        $file = $this->openUpload($path);
        $this->checkHash($file);
        $this->parse($file);
        fclose($file);

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
            return NULL;
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


    public function attribute($name, $value=NULL){
        if(isset($value)){
            if(empty($this->xml[$name])){
       	        $this->xml->addAttribute($name, $value);
       	    } else {
                $this->xml[$name] = $value;
       	    }
       	}
        return (string) $this->xml[$name];
    }


    public function fileName($value=NULL){
        return $this->attribute('fileName', $value);
    }


    public function title($value=NULL){
        return $this->attribute('title', $value);
    }


    public function game($value=NULL){
        return $this->attribute('game', $value);
    }


    public function time($value=NULL){
        return $this->attribute('time', $value);
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



    private function parse($file){
        if($this->valid){
            $lineNo = 0;
            $rankCount = 0;
            while($this->valid && !feof($file)) {
            //                $line = SECURITYsanitizeHTML(fgets($file));
               $line = fgets($file);

                $lineNo++;
                $tupple = explode(',', $line);
                if (count($tupple) == 2){
                    $rank = (int) trim($tupple[0]);
                    $sha256 = trim($tupple[1]);
                    if ($rank > 0 && $rank < 10000 && strlen($sha256) == 64){
                        $child = $this->xml->addChild('sha256', $sha256);
                        $child->addAttribute('rank', $rank);
                        $rankCount++;
                    } else {
                        $this->transcript .= "data on line $lineNo ignored<br>$line";
                    }
                }
            }
            $this->transcript .= "$rankCount player rankings found<br>";
        }
    }
    
    
    public function save(){
        return self::writeXML(
            $this->xml, 
            self::PATH_UPLOAD, 
            $this->fileName() . self::XML
        );
    }


    public function retrieve($rankingID){
        return self::readXML( 
            self::PATH_UPLOAD, 
            $rankingID . self::XML
        );
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
    public function insert(){
        $rankParity = self::RANK_PARITY;

        $rankingID = $this->id();
        $game = $this->game();

        $ranks = array();
        $anonIDs = $this->xml->xpath("sha256");
        foreach($anonIDs as $anonID){
            $anonRank = (int) $anonID['rank'];
            $ranks[$anonRank] = (string) $anonID;
        }

        foreach($ranks as $anonRank => $anonID){
            $anon = new Player($anonID, TRUE);

            foreach($rankParity as $rnk => $parity){
                $rnk = (int)$rnk;
                $parity = (float)$parity;
                $rivalRank = $anonRank + $rnk;
                if (isset($ranks[$rivalRank])){
                    $rid = $ranks[$rivalRank];
                    $anon->rankAdd($rankingID, $game, $rid, $parity);
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
    public function extract(){
        $rankingID = $this->id();
        $anonIDs = $this->xml->xpath("sha256");
        foreach($anonIDs as $anonID){
            $anon = new Player($anonID);
            if (isset($anon)
            && $anon->ok()){
                $anon->removeRanks($rankingID);
            }
// possible to remove player here if there is no email and no other ranks (or other data)
        }
        $this->status('uploaded');
        $this->save();
    }

}

?>
