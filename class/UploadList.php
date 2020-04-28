<?php

require_once 'Card.php';

/*******************************************************************************
    Class UploadList replicates a html snippet for each qwik record.
    The html snippet is embedded in a html template and located by a <div id=''>.
*******************************************************************************/

class UploadList extends Card {


    /*******************************************************************************
    Class UploadList is constructed with a html template.

    $html String a html document containing a div to be replicated.
    $id   String a html div id to identify the html snippet to be replicated.
    *******************************************************************************/
    public function __construct($html, $id=NULL){
        parent::__construct($html, $id);
    }


    public function replicate($html, $variables){
        $player = $this->player();
        if (is_null($player)){ return '';}
  
        $group = '';
        $uploadIDs = $player->uploadIDs();
        foreach($uploadIDs as $uploadID) {
            $ranking = $player->rankingGet($uploadID);
            $status = $ranking->status();
            $uploadVars = array(
                'status'   => $status,
                'fileName' => $ranking->fileName(),
                'crossAct' => $status == 'uploaded' ? 'delete' : 'deactivate',
                'tickIcon' => $status == 'uploaded' ? self::TICK_ICON : '',
                'title'    => $ranking->title(),
                'game'     => $ranking->game(),
                'time'     => $ranking->time()
            );
            $vars = $variables + $uploadVars;
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }

}


?>
