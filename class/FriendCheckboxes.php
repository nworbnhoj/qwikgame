<?php

require_once 'Card.php';

/*******************************************************************************
 * Class Checkboxes constructs checkbox elements of the form:
 *   <input type='checkbox' name='$name[]' value='$key'>$key
 *******************************************************************************/

class FriendCheckboxes extends Card {

    public function __construct($template, $id){
        parent::__construct($template, $id);
    }


    protected function loadUser($uid){
        return new Player($uid);
    }


    public function replicate($html, $variables){
        $player = $this->player();
        if (is_null($player)){ return '';}

        $checkboxes = '';
        $values = $player->friends();
        foreach($values as $key => $val){
            $box = str_replace('[key]', $key, $html);
            $box = str_replace('[val]', $val, $box);
            $checkboxes .= "$box\n";
        }
        return $checkboxes;
    }

}


?>
