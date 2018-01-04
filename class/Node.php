<?php


class Node {

    private $rid;
    private $parity;
    private $rely;
    private $date;

    public function __construct($rivalID, $parity=NULL, $rely=NULL, $date=NULL){
        $this->rid = (string)$rivalID;
        $this->parity = "$parity";
        $this->rely = $this->rely($rely, $date);
    }


    public function rid(){
        return $this->rid;
    }


    public function parity(){
        return $this->parity;
    }


    public function rely($rely=null, $date=NULL){
        if(!is_null($rely)){
            $this->rely = $rely;
//            $this->rely = $this->ebb($rely, $date);  // depreciate with age
        }
        return $this->rely;
    }


    public function orb($game){
        $rival = new Player($this->rid());
        if(isset($rival)){
            return $rival->orb($game);
        }
    }

}

?>
