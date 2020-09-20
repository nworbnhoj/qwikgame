<?php


class Node {

    public static function par($pid, $parity, $rely, $date=NULL) {
        $instance = new self();
        $instance->loadParity($pid, $parity, $rely, $date);
        return $instance;
    }


    public static function rank($rid, $pid) {
        $instance = new self();
        $instance->loadRank($rid, $pid);
        return $instance;
    }


    public static function inverse($pid, $node) {
        $instance = new self();
        $nodeParity = $node->parity();
        $invParity = ! is_null($nodeParity) ? -1 * $nodeParity : NULL;
        $instance->loadParity($pid, $invParity, $node->rely(), $node->date());
        return $instance;
    }


    private $id;         
    private $parity;
    private $rely;
    private $date;
    private $orb;
    
    private $rid;        // the ranking ID
    private $rankedPid;  // the ranked Player ID

    public function __construct(){}


    protected function loadParity($pid, $parity, $rely, $date){
      $this->id = (string)$pid;
      $this->parity = floatval($parity);
      $this->rely = $this->rely(floatval($rely), $date);
      // $this->date = DateTime::createFromFormat('d-m-Y', $date);
    }
    
    
    protected function loadRank($rid, $pid){
      $this->rid = $rid;
      $this->rankedPid = $pid;
    }
    
    
    public function resolveRank($baseRank, $pid, $pidRank, $ranking){
      $this->id = $pid;
      $this->parity = $ranking->parity($baseRank, $pidRank);
      $this->rely = 3.0;
      // $this->date = DateTime::createFromFormat('d-m-Y H:i:s', $ranking->time());
    }


    public function id(){
        return $this->id;
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


    public function date(){
      return $this->date;
    }


    public function orb($orb=NULL){
        if (!is_null($orb)){
            $this->orb = $orb;
        }
        return $this->orb;
    }


    public function rid(){
        return $this->rid;
    }


    public function rankedPid(){
        return $this->rankedPid;
    }

}

?>
