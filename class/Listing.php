<?php

require_once 'Page.php';

/*******************************************************************************
    Class Page constructs an html page beginning with a html template; 
    replicating html elements (such as rows in a <table>); replacing
    [variables]; and making {translations}.
*******************************************************************************/

class Listing extends Page {

    const BASE_CLASS_FLAG = 'base';
    const BASE_PATH = "contains(concat(' ',normalize-space(@class),' '),' ".Listing::BASE_CLASS_FLAG." ')]";

    /**
    * class='base' is used to flag a hidden element to be used as a template
    * for elements in a Listing. The class='base' must be removed from the 
    * element so that it becodes visible (and is not used as a basis for  json listing
    */
    static public function extractBase($html, $id){
        if(is_null($html) || is_null($id)) { return NULL; }

        // tidy the $html to ensure the a SimpleXMLElement can parse OK
        $config = array('output-xhtml' => true, 'indent' => true, 'drop-empty-elements' => false);
        $tidy = new tidy;
        $tidy->parseString($html, $config, 'utf8');
        $tidy->cleanRepair();

        // create a SimpleXMLElement from $html to assist manipulation
        $xml = new SimpleXMLElement((string)$tidy);
        $xml->registerXPathNamespace("x", "http://www.w3.org/1999/xhtml");

        // select a <div> with id='$id' and class='base'
        $path = "//x:div[@id='$id' and ".Listing::BASE_PATH;
        $baseXML = $xml->xpath($path)[0]; 

        if(is_null($baseXML)){
            self::logMsg("failed to extract base = '$id'");
            $baseXMLtxt = '';
        } else {
            $baseXMLtxt = $baseXML->asXML();
        }

        return $baseXMLtxt;
    }


    /*******************************************************************************
    Class Listing is constructed with a html template.

    $templateName  String  fileName containing the html template.
    *******************************************************************************/
    public function __construct($html, $id){
        parent::__construct(self::extractBase($html, $id));

         // if a base was not provided to the constructor then get the base from the 
         // (json) request (see *.listing.json.php). 
        if(empty($this->template())){
            $reqHtml = $this->req('html');
            if(!empty($reqHtml)){
                $this->template($reqHtml);
            }
        }

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
        $retranslated = $this->translate($replicated, $variables);
        $populated = $this->populate($retranslated, $variables);
        return html_entity_decode($populated);
    }


    /**
    * return $html with class='base' inserted.
    * class='base' is hidden by qwik.css and used by a listing.json.php call to update a Listing
    * remove the 'base' from the class (elements with class='base' are hidden with qwik.css)
    */
    public function replicate($html){
        $html = html_entity_decode($html);
        return $this->removeFlag($html);
    }


    /**
    * Removes class='base' from the html.
    * class='base' is used to flag a hidden element to be used as a template
    * for elements in a Listing. The class='base' must be removed from the 
    * element so that it becomes visible (and is not used as a basis for
    * a subsequet (json) listing
    */
    private function removeFlag($html){
        $d ='"';
        $base = Listing::BASE_CLASS_FLAG;
        $pattern = "/class(\s*)=(\s*)($d|')(.*?)(\s*)$base(\s*)(.*?)('|$d)/";
        $replacement = "class=$3$4 $7$8";
        return preg_replace($pattern, $replacement, $html);
    }


    private function removeFlag1($html){
        $baseXML = new SimpleXMLElement($html);
        $baseClassString = (string)$baseXML['class'];
        $baseClassArray = explode(" ", $baseClassString);
        $key = array_search(Listing::BASE_CLASS_FLAG, $baseClassArray);
        if ($key !== false) {
            unset($array[$key]);
        }
        $baseXML['class'] = implode(" ", $baseClassArray);
        return $baseXML->asXML();
    }






}


?>
