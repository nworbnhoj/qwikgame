<?php

require_once 'Base.php';

/*******************************************************************************
    Class Repost replicates a html snippet post element.
    The html snippet is embedded in a html template and located by a <div id=''>.
*******************************************************************************/

class Repost extends Base {

    private $request;
    /*******************************************************************************
    Class Repost is constructed with a html template.

    $html String a html document containing a div to be replicated.
    $id   String a html div id to identify the html snippet to be identified.
    *******************************************************************************/
    public function __construct($html, $id, $request){
        parent::__construct($html, $id);
        $this->request = $request;
    }


    public function replicate($html){
        $html = parent::replicate($html); // removes 'base' class
        $request = $this->request;
        $group = '';
        foreach($request as $name => $value){
            if(is_array($value)){
                $nameII = "$name" . "[]";
                foreach($value as $val){
                    $htm = str_replace('[name]', $nameII, $html);
                    $htm = str_replace('[value]', $val, $htm);
                    $group .= "$htm\n";
                }
            } elseif(isset($value)){
                $htm = str_replace('[name]', $name, $html);
                $htm = str_replace('[value]', $value, $htm);
                $group .= "$htm\n";
            }
        }
        return $group;
    }

}


?>
