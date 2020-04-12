<?php

require_once 'Page.php';

/*******************************************************************************
    Class Page constructs an html page beginning with a html template; 
    replicating html elements (such as rows in a <table>); replacing
    [variables]; and making {translations}.
*******************************************************************************/

class Base extends Page {

    const BASE_CLASS_FLAG = 'base';
    const BASE_PATH = "contains(concat(' ',normalize-space(@class),' '),' ".Base::BASE_CLASS_FLAG." ')";

    /**
    * class='base' is used to flag a hidden element to be used as a template
    * for elements in a Base. The class='base' must be removed from the 
    * element so that it becodes visible (and is not used as a basis for  json listing
    */

    /*******************************************************************************
    Class Base is constructed with a html template.

    $templateName  String  fileName containing the html template.
    *******************************************************************************/
    public function __construct($html, $id=NULL){
        $html = html_entity_decode($html);  
        $baseHTML = '';
      
        $doc = new DOMDocument('1.0', 'UTF-8');
        // todo tidy html templates on save
        $internalErrors = libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        $element = empty($id) ? $doc->documentElement->firstChild->firstChild : $doc->getElementById($id);
        if (isset($element)){
            $element->removeAttribute('id');  //remove the id attribute
            $classes = explode(" ", $element->getAttribute('class'));
            if (($key = array_search(Base::BASE_CLASS_FLAG, $classes)) !== false) {
                unset($classes[$key]);            // remove the base class
                $element->setAttribute('class', implode(' ', $classes));
            }
            $baseHTML = $doc->saveHTML($element);
        } else {
            self::logMsg("failed to extract a suitable base: id=$id");
        }
        parent::__construct($baseHTML);
    }


    public function serve(){
        return NULL;
    }


    public function processRequest(){
        return NULL;
    }


    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();
        $replicated = $this->replicate($html);
        $retranslated = $this->translate($replicated);
        $populated = $this->populate($retranslated, $variables);
        return html_entity_decode($populated);
    }


    /**
    * return a single baseElement (ie $html without an id attribute or class='base'
    */
    public function replicate($html){
        return $html;
    }

}


?>
