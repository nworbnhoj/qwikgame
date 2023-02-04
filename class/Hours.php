<?php

Class Hours {

    // Encapsulates a bitfield representing the 24hrs in a day

    const HRS_24 = 16777215;
    const HRS_6AM_to_8PM = 2097088;
    const HRS_9AM_to_5PM = 261632;

    private $bits;


    public function __construct($bits=0){
        $this->bits = intval($bits);
    }
    
    public function __toString(){
        return sprintf("%1$25b", $this->bits);
    }

    
    public function bits(){
        return $this->bits;
    }
    
    
    public function set($hour){
        $this->bits = $this->bits | (2 ** $hour);
    }


    public function set_span($first, $last){
        $first = max($first, 0);
        $last = min($last, 23);
        for ($hour = $first; $hour <= $last; $hour++){
            $this->set($hour);
        }
    }
    
    
    public function get($hour){
        return $this->bits & (2 ** $hour);
    }


    public function none(){
        return $this->bits === 0;
    }


    public function equals($that) {
        return $this->bits == $that->bits();
//        return ($this::class === $that::class) && ($bits != NULL) 
//             ? $this->bits == $that->bits()
//             : $this == $that;
    }
    
    
    /**
    /* Includes the $hours provided into $this
    /* returns true if $this->bits has changed as a result;
    **/
    public function append($hours){
        $priorBits = $this->bits;
        $this->bits = ($this->bits | $hours->bits);
        return $this->bits != $priorBits;
    }


    /**
    /* Includes only the $hours provided in $this (ie the intersection of $hours and $this)
    /* returns true if $this->bits has changed as a result;
    **/
    public function includeOnly($hours){
        $priorBits = $this->bits;
        $this->bits = ($this->bits & $hours->bits);
        return $this->bits != $priorBits;
    }
    
    
    public function purge(){
        return $this->bits === 0;
    }
    
    
    // Returns an array of hours
    public function roster(){
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
        return $this->roster()[0];
    }


    public function last(){
        return max($this->roster());
    }
}



?>
