<?php

require_once 'Listing.php';

/*******************************************************************************
 * Class Checkboxes constructs checkbox elements of the form:
 *   <input type='checkbox' name='$name[]' value='$key'>$key
 *******************************************************************************/

class FriendCheckboxes extends Listing {

    public function __construct($template){
        parent::__construct($template);
    }


    public function replicate($html){
        $player = $this->player();
        if (is_null($player)){ return '';}

        $html = parent::replicate($html); // removes 'base' class

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
