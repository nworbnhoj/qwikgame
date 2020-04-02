<?php

require_once 'Card.php';
require_once 'Page.php';

/*******************************************************************************
    Class PendingList replicates a html snippet for each pending translation 
    record.
    The html snippet is embedded in a html template and located by a <div id=''>.
*******************************************************************************/

class PendingList extends Card {

    private $pending;

    /*******************************************************************************
    Class PendingBase is constructed with a html template.

    $html String a html document containing a div to be replicated.
    $id   String a html div id to identify the html snippet to be identified.
    *******************************************************************************/
    public function __construct($html){
        parent::__construct($html);

        $this->pending = new Translation('pending.xml');
    }


    public function replicate($html){
        $player = $this->player();
        if (empty($player->admin())){
            $this->logout();
            return;
        }

//        $group = $html;  // if more than one json update is required, may leave a copy of base here
        $html = parent::replicate($html); // removes 'base' class
        $group = '';
        $phraseBook = parent::$phraseBook;
        $pending = $this->pending;
        if(!$phraseBook || !$pending){ return; }
        $langs = $pending->languages();
        $keys = $pending->phraseKeys();
        foreach($keys as $key){
            $en_phrase = $phraseBook->phrase($key, 'en');
            foreach($langs as $lang => $native){
                $phrase = $pending->phrase($key, $lang, '');
                if(isset($phrase)){
                    $translationVars = array(
                        'key'       => $key,
                        'en_phrase' => $en_phrase,
                        'lang'      => $lang,
                        'phrase'    => $phrase
                    );
                    $vars = $translationVars + Page::$icons;
                    $group .= $this->populate($html, $vars);
                }
            }
        }
        return $group;
    }

}


?>
