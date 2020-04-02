<?php

require_once 'Base.php';

/*******************************************************************************
    Class Card represents a portion of Player data.
*******************************************************************************/

class Card extends Base {

    /*******************************************************************************
     * Class Card is constructed by extracting a html element with class='base' and id=$id.
     *
     * $html  String  html text containing and element  with class='base' and id=$id.
     *******************************************************************************/
    public function __construct($html, $id){
        parent::__construct($html, $id);

        $player = $this->player();
        if (is_null($player)
        || !$player->ok()){
            $this->logout();
            return;
        }
    }

}

?>
