<?php


class Node {

    private $rid;
    private $parity;
    private $rely;
    private $date;
    private $orb;

    public function __construct($rivalID, $parity=NULL, $rely=NULL, $date=NULL){
        $this->rid = (string)$rivalID;
        $this->parity = floatval($parity);
        $this->rely = $this->rely(floatval($rely), $date);
        $this->date = DateTime::createFromFormat('d-m-Y', $date);
    }


    public function rid(){
        return $this->rid;
    }


    public function parity(){
        return $this->parity;
    }


    public function rely($rely=NULL, $date=NULL){
        if(!is_null($rely)){
            $this->rely = $rely;
//            $this->rely = $this->ebb($rely, $date);  // depreciate with age
        }
        return $this->rely;
    }


    public function orb($orb=NULL){
        if (!is_null($orb)){
            $this->orb = $orb;
        }
        return $this->orb;
    }

}

?>
