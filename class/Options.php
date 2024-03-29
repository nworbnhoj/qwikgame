<?php

require_once 'Page.php';

/*******************************************************************************
    Class Options constructs <option> elements for a <datalist>
*******************************************************************************/

class Options extends Page {

    const VAL = "[val]";
    const KEY = "[key]";
    const KEYVALUE_TEMPLATE = "<option value='".self::KEY."'>".self::VAL."</option>";
    const VALUE_TEMPLATE = "<option value='".self::VAL."'>".self::VAL."</option>";
    const DATALIST_TEMPLATE = "<option value='".self::VAL."'>";
    const CHECKBOX_TEMPLATE = "<input type='checkbox' name='[name]' value='".self::KEY."'>".self::VAL;

    private $values;


    public function __construct($values, $template = self::KEYVALUE_TEMPLATE){
        parent::__construct($template);
        $this->values = $values;
    }


    protected function loadUser($uid){
        return new Player($uid);
    }


    public function values($values=NULL){
        if(isset($values) && is_array($values)){
            $this->values = $values;
        }
        return $this->values;
    }


    public function serve($history=NULL){
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

    
    public function favoriteVenues($game){
      $player = $this->player();
      return isset($player) ? $player->favoriteVenues($game) : array();
    }
    
    
    public function matchVenues($game){
      $player = $this->player();
      return isset($player) ? $player->matchVenues($game) : array();
    }
    
    
    public function regions(){
      $player = $this->player();
      return isset($player) ? $player->regions() : array();
    }

}


?>
