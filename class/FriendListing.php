<?php

require_once 'Listing.php';

/*******************************************************************************
    Class FriendListing replicates a html snippet for each qwik record.
    The html snippet is embedded in a html template and located by a <div id=''>.
*******************************************************************************/

class FriendListing extends Listing {


    /*******************************************************************************
    Class FriendListing is constructed with a html template.

    $html String a html document containing a div to be replicated.
    $id   String a html div id to identify the html snippet to be identified.
    *******************************************************************************/
    public function __construct($html){
        parent::__construct($html);
    }


    public function replicate($html){
//        $group = $html;  // if more than one json update is required, may leave a copy of base here
        $html = parent::replicate($html); // removes 'base' class
        $group="";
        $player = $this->player();
        $playerVars = $this->playerVariables($player);
        $reckoning = $player->reckon("rival");
        $emails = array();
        foreach($reckoning as $reckon){
            $email = (string) $reckon['email'];
            if (!array_key_exists($email, $emails)){
                $emails[$email] = TRUE;
                $parity = (int) $reckon['parity'];
                $game = (string) $reckon['game'];
                $reckonVars = array(
                    'id'        => $reckon['id'][0],
                    'email'     => $email,
                    'gameName'  => self::gameName($game),
                    'parity'    => self::parityStr($parity)
                );
                $vars = $playerVars + $reckonVars + self::$icons;
                $group .= $this->populate($html, $vars);
            }
        }
        return $group;

    }

}


?>
