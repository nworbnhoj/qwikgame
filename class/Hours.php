<?php

Class Hours {

    // Encapsulates a bitfield representing the 24hrs in a day

    const HRS_24 = 33554431;
    const HRS_6AM_to_8PM = 16777215;

    private $bits;


    public function __construct($bits=0){
        $this->bits = $bits;
    }
    
    
    public function bits(){
        return $this->bits;
    }
    
    
    public function set($hour){
        $this->bits = $this->bits | (2 ** $hour);
    }
    
    
    public function get($hour){
        return $this->bits & (2 ** $hour);
    }


    public function intersection($hours){
        return new Hours($this->bits & $hours->bits());
    }
    
    
    public function union($hours){
        return new Hours($this->bits | $hours->bits());
    }
    
    
    public function include($hours){
        $this->bits = $this->bits | $hours->bits();
    }
    
    
    public function empty(){
        return $bits === 0;
    }
    
    
    // Returns an array of hours
    public function list(){
        $hours = array();
        $mask = 1;
        for ($hour = 0; $hour < 24; $hour++){
            if ($this->bits & $mask){
                $hours[] = $hour ;
            }
            $mask = $mask * 2;
        }    
        return $hours;
    }


    public function first(){
        return $this->hours()[0];
    }


    public function last(){
        return max($this->hours());
    }
}



?>
