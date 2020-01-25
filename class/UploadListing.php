<?php

require_once 'Listing.php';

/*******************************************************************************
    Class UploadListing replicates a html snippet for each qwik record.
    The html snippet is embedded in a html template and located by a <div id=''>.
*******************************************************************************/

class UploadListing extends Listing {


    /*******************************************************************************
    Class UploadListing is constructed with a html template.

    $html String a html document containing a div to be replicated.
    $id   String a html div id to identify the html snippet to be replicated.
    *******************************************************************************/
    public function __construct($html){
        parent::__construct($html);
    }


    public function replicate($html){
        $html = parent::replicate($html); // removes 'base' class       
        $group = '';
        $player = $this->player();
        $uploadIDs = $player->uploadIDs();
        foreach($uploadIDs as $uploadID) {
            $ranking = $player->rankingGet($uploadID);
            $status = $ranking->status();
            $vars = array(
                'status'   => $status,
                'fileName' => $ranking->fileName(),
                'crossAct' => $status == 'uploaded' ? 'delete' : 'deactivate',
                'tickIcon' => $status == 'uploaded' ? self::TICK_ICON : '',
                'title'    => $ranking->title(),
                'game'     => $ranking->game(),
                'time'     => $ranking->time()
            );
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }

}


?>
