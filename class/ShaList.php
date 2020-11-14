<?php

require_once 'Base.php';
require_once 'Page.php';

/*******************************************************************************
    Class ShaList replicates a html snippet for each template element with 
    class='base'.
*******************************************************************************/

class ShaList extends Base {

  /*******************************************************************************
  Class ShaList is constructed with a html template.

  $html String a html document containing a div to be replicated.
  $id   String a html div id to identify the html snippet to be identified.
  *******************************************************************************/
  public function __construct($html, $id=NULL){
    parent::__construct($html, $id);
    $this->pending = new Translation('pending.xml');
  }


  public function replicate($html, $variables){
    $group = '';
    $internalErrors = libxml_use_internal_errors(true);
    
    $languages = Qwik::fileList(PATH_LANG);  // translated template directories
    foreach($languages as $lang){
      if(!is_dir(PATH_LANG."$lang")                               // skip files
      || "$lang" === "."
      || "$lang" === ".."){
        continue;
      }
      
      $names = Qwik::fileList(PATH_LANG."$lang");         // html template list
      foreach($names as $name){
        if(!is_file(PATH_LANG."$lang/$name")){ continue; }         // skip dirs
        
        $name = substr($name, 0, strlen($name) - 5);              // trim .html
        $template = Html::readTemplate($name, $lang);
        $ids  = $this->ids($template);        
        
        foreach($ids as $id){
          $base = new Base($template, $id);
          $vars = array(
            'hash' => $base->hash(),
            'name' => "$lang $id"
          );
          $group .= $this->populate($html, $vars);          
        }
      }
    }
    return $group;
  }
  
  
  private function ids($template){
    $ids = array();
    // load html template and select <div class="base">
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->loadHTML($template);
    $xpath = new DOMXpath($doc);
    $elements = $xpath->query("//div[contains(@class, 'base')]");
    foreach($elements as $element){
      $id = $element->attributes->getNamedItem('id')->textContent;
      $ids[] = $id;
    }
    return $ids;
  }

}


?>
