<?php

require_once 'Page.php';

/*******************************************************************************
    Class Options constructs <option> elements for a <datalist>
*******************************************************************************/

class Options extends Page {

    const VAL = "[val]";
    const KEY = "[key]";
    const SELECT_TEMPLATE = "<option value='".self::KEY."'>".self::VAL."</option>";
    const DATALIST_TEMPLATE = "<option value='".self::VAL."'>";

    private $values;


    public function __construct($values, $isDatalist = TRUE){
        parent::__construct($isDatalist ? self::DATALIST_TEMPLATE : self::SELECT_TEMPLATE);
        $this->values = $values;
    }


    public function serve(){
        return NULL;
    }


    public function processRequest(){
        return NULL;
    }


    public function replicate($html){
        $options = '';
        foreach($this->values as $key => $val){
            $opt = str_replace(self::KEY, $key, $html);
            $opt = str_replace(self::VAL, $val, $opt);
            $options .= "$opt\n";
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
