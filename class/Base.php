<?php

require_once 'Page.php';

/*******************************************************************************
    Class Page constructs an html page beginning with a html template; 
    replicating html elements (such as rows in a <table>); replacing
    [variables]; and making {translations}.
*******************************************************************************/

class Base extends Page {

    const BASE_CLASS_FLAG = 'base';

    /**
    * class='base' is used to flag a hidden element to be used as a template
    * for elements in a Base. The class='base' must be removed from the 
    * element so that it becodes visible (and is not used as a basis for  json listing
    */
    
    private $hash;

    /*******************************************************************************
    Class Base is constructed with a html template.

    $templateName  String  fileName containing the html template.
    *******************************************************************************/
    public function __construct($html, $id=NULL){
        $baseHTML = '';
        $doc = new DOMDocument('1.0', 'UTF-8');
        $internalErrors = libxml_use_internal_errors(true);  // todo tidy html templates on save
        $doc->loadHTML($html);                               // better to use LIBXML_HTML_NOIMPLIED here
        $element = empty($id) ? $doc->documentElement->firstChild->firstChild : $doc->getElementById($id);
        if (isset($element)){
            $this->hash($doc->saveHTML($element));
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
    
    
    
    /**************************************************************************
     * Computes, stores and returns the sha-256 hash of the base html
     *  
     * Includes an important kludge because:
     * - php doc->saveHTML() always outputs 'selected'    regardless of the input html
     * - js  node.outerHTML  always outputs 'selected=""' regardless of the input html
     * - the hash of these 2 must be identical for use in Filter::html()
     *************************************************************************/
    public function hash($html=NULL){
      if(!is_null($html)){
        $kludge = str_replace('selected>', 'selected="">', $html);
        $this->hash = hash('sha256', $kludge);
      }
      return $this->hash;
    }


    public function serve($history=NULL){
        return NULL;
    }


    public function processRequest(){
        return NULL;
    }


    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();
        $html = $this->replicate($html, $vars);
        $html = $this->translate($html);
        return $html;
    }


    public function replicate($html, $variables){
        return $this->populate($html, $variables);
    }

}


?>
