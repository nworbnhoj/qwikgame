<?php


require_once 'Qwik.php';
require_once 'Player.php';

class Ranking extends Qwik {

    const CSV = '.csv';
    const XML = '.xml';
    
    // regex pattern to match any invalid line in a Ranking file
    const INVALID = "#(?m)^(?!\d{1,4},(?:[a-z0-9]{64})$)#";    


    static function exists($id){
      return file_exists(PATH_UPLOAD.$id.self::XML);
    }
    
    /**************************************************************************
     * Validates each line in a ranking.csv
     * Each line must contain [digit,sha256]
     * digit must be a number 0-9999
     * sha256 must be exactly 64 lowercase characters [a-z] and digits [0-9]
     * example 0,0c49654105084fc4ac339ecb69ce44421f28a67bb129639f8a2b3a4acc5f3c2d
     * @return Integer the first line # which failed validation of 0 on success
     *************************************************************************/
    static function validate($file){
      $badLineNumber = 0;
      $text = file_get_contents($file);
      $matches = array();
      $fails = preg_match_all(self::INVALID, $text, $matches, PREG_OFFSET_CAPTURE);
      if ($fails > 0){
        $firstFail = $matches[0][0];
        $position = $firstFail[1];                 // position of invalid match
        $before = substr($text, 0, $position);     // text before invalid match
        $lineCount = substr_count($before, "\n");
        $badLineNumber = $lineCount + 1;
      }
      return $badLineNumber;
    }


    private $xml;
    public $transcript;
    public $valid;

    public function __construct($id, $game=NULL){
        parent::__construct();
        $this->valid = true;
        if(self::exists($id)){
            $this->xml = $this->retrieve($id);
        } else if (isset($game)) {
          $filename = PATH_UPLOAD.$id.self::CSV;
          $badLineNumber = self::validate($filename);
          if( $badLineNumber <= 0){
            $this->xml = new SimpleXMLElement("<upload></upload>");
            $this->xml->addAttribute('id', $id);
            $this->processUpload($id);
            if ($this->valid){
                $date = date_create();
                $this->xml->addAttribute('time', $date->format('d-m-Y H:i:s'));
                $this->xml->addAttribute('game', $game);
                $this->xml->addAttribute('status', 'uploaded');
                $this->save();
            }
          } else {
            $this->transcript .= "Validation failed on line $badLineNumber.\n";
            $this->valid = false;
          }  
        } else {
            self::logMsg("Ranking construct() missing game $id");
        }
    }


    private function processUpload($id){
        $file = $this->openUpload($id);
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


    private function openUpload($id){
        if (!$this->valid){
            return NULL;
        }
        // open the uploaded file
        $filename = PATH_UPLOAD.$id.self::CSV;
        $file = fopen($filename, "r");
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


    public function id($value=NULL){
        return $this->attribute('id', $value);
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


    public function parity($rankA, $rankB){
      $diff = $rankA - $rankB;
      if($diff > 25){
        return -2;
      } else if($diff > 10){
        return -1;
      } else if($diff > -10){
        return 0;
      } else if($diff > -25){
        return +1;
      } else {
        return +2;
      }
    }
    
    
    public function ranks(){
      $ranks = array();
      $anonIDs = $this->xml->xpath("sha256");
      foreach($anonIDs as $anonID){
        $anonRank = (int) $anonID['rank'];
        $ranks[(string) $anonID] = $anonRank;
      }
      return $ranks;
    }


    private function checkHash($file){
        if($this->valid){
            $facilitatorSHA256 = hash('sha256', 'facilitator@qwikgame.org');

            $line = fgets($file);
            $lines = explode(',', $line);
            $testSHA256 = isset($lines[1]) ? trim($lines[1]) : '' ;

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
               $line = fgets($file);

                $lineNo++;
                $tupple = explode(',', $line);
                if (count($tupple) == 2){
                    $r = $tupple[0];
                    $s = $tupple[1];
                    $rank = isset($r) ? (int) trim($r) : -1 ;
                    $sha256 = isset($s) ? trim($s) : '' ;
                    if ($rank > 0 && $rank < 10000 && strlen($sha256) == 64){
                        $child = $this->xml->addChild('sha256', htmlspecialchars($sha256));
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
            PATH_UPLOAD, 
            $this->id().self::XML
        );
    }


    public function retrieve($id){
        return self::readXML( 
            PATH_UPLOAD, 
            $id . self::XML
        );
    }


    public function status($status=NULL){
        if (isset($status)){
            $this->xml['status'] = $status;
        }
        return (string) $this->xml['status'];
    }



    /**
    * Process a User request to activate a set of uploaded player rankings.
    *
    * $ranking    XML    The uploaded rankings
    *
    * The rankings are inserted into the XML data of the ranked players
    * (creating new anon players as required)
    *
    * @throws RuntimeException if there are problem activating the Ranking.
    */
    public function insert(){

        $rankingID = $this->id();
        $game = $this->game();
        $ranks = $this->ranks();
        foreach($ranks as $anonID => $anonRank){
            try {
                $anon = new Player($anonID, TRUE);
                $anon->rankAdd($rankingID, $game);
                $anon->save();
            } catch (RuntimeException $e){
                self::logThown($e);
                self::logMsg("failed to create new Anon Player $anonID from uploaded Ranking $rankingID");
            }
        }
        $this->status('active');
        try{
            $this->save();
        } catch (RuntimeException $e){
            self::logThrown($e);
            throw new RuntimeException("Failed to save Ranking $rankingID");
        }
    }


    /*******************************************************************************
    Process a User request to de-activate a set of uploaded player rankings.

    $ranking    XML    The uploaded rankings
    ********************************************************************************/
    public function extract(){
        $rankingID = $this->id();
        $anonIDs = $this->xml->xpath("sha256");
        foreach($anonIDs as $anonID){
            try {
                $anon = new Player($anonID);
                if (isset($anon)
                && $anon->ok()){
                    $anon->removeRanks($rankingID);
                }
                $anon->save();
            } catch (RuntimeException $e){
                self::logThown($e);
                self::logMsg("failed to extract Player $anonID from Ranking $rankingID");
            }
// possible to remove player here if there is no email and no other ranks (or other data)
        }
        $this->status('uploaded');
        try{
            $this->save();
        } catch (RuntimeException $e){
            self::logThrown($e);
            throw new RuntimeException("Failed to save Ranking $rankingID");
        }
    }

}

?>
