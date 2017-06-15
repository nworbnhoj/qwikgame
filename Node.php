<?php


class Node {

    private $rid;
    private $parity;
    private $rely;
    private $date;
    private $orb;

    public function __construct($rivalID, $log, $parity=NULL, $rely=NULL, $date=NULL){
        $node = array();
        $this->rid = "$rivalID";
        $this->parity = "$parity";
        $this->rely = $this->rely($rely, $date);
        $this->orb = new Orb($log);
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


    public function orb(){
        return $this->orb;
    }

}

?>

