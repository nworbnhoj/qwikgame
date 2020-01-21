<?php

require_once 'Page.php';

/*******************************************************************************
    Class Options constructs <option> elements for a <datalist>
*******************************************************************************/

class Options extends Page {

    const VAL = "[val]";
    const OPTION_TEMPLATE = "<option value='".self::VAL."'>\n";

    private $values = array();


    public function __construct(){
        parent::__construct(self::OPTION_TEMPLATE);
    }


    public function values($values=NULL){
        if(isset($values) && is_array($values)){
            $this->values = $values;
        }
        return $this->values;
    }


    public function serve(){
        return NULL;
    }


    public function processRequest(){
        return NULL;
    }


    public function replicate($html){
        $options = '';
        foreach($this->values as $val){
            $options .= str_replace(self::VAL, $val, $html);
        }
        return $options;
    }


    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();
        $html = $this->replicate($html);
        $html = parent::make($variables, $html);
        return $html;
    }

}


?>
