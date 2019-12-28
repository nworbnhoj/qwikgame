<?php

require_once 'Listing.php';

/*******************************************************************************
    Class Page constructs an html page beginning with a html template; 
    replicating html elements (such as rows in a <table>); replacing
    [variables]; and making {translations}.
*******************************************************************************/

class FriendListing extends Listing {


    /*******************************************************************************
    Class FriendListing is constructed with a html template.

    $templateName  String  fileName containing the html template.
    *******************************************************************************/
    public function __construct($html, $id='friend'){
        parent::__construct($html, $id);
    }


    public function replicate($html){
        $html = html_entity_decode($html);
        $group="";
//        $group = $html;  // if more than one json update is required, may leave a copy of base here
        $html = parent::replicate($html); // removes 'base' class
        $player = $this->player();
        $playerVars = $this->playerVariables($player);
        $reckoning = $player->reckon("rival");
        $emails = array();
        foreach($reckoning as $reckon){
            $email = (string) $reckon['email'];
            if (!array_key_exists($email, $emails)){
                $emails[$email] = TRUE;
                $parity = (int) $reckon['parity'];
                $game = $reckon['game'];
                $reckonVars = array(
                    'id'        => $reckon['id'][0],
                    'email'     => $email,
                    'game'      => self::qwikGames()["$game"],
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
