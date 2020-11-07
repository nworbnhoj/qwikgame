<?php

require_once 'Page.php';
require_once 'UploadList.php';


class UploadPage extends Page {

    public function __construct($templateName='upload'){
        parent::__construct(NULL, $templateName);

        $player = $this->player();
        if (is_null($player)
        || !$player->ok()){
            $this->logout();
            return;
        }
    }
	
	
    public function processRequest(){
      $result = parent::processRequest();
      if(!is_null($result)){ return $result; }     // request handled by parent
        
      $player = $this->player();
      $qwik = $this->req('qwik');
      if (isset($player) && isset($qwik)){
        $id = $this->req('id');
        $filename = $player->upload($id);
        switch ($qwik) {
          case 'upload':
            $game = $this->req('game');
            $title = $this->req('title');
            if(isset($game) && isset($title)){
              $msg = $player->rankingUpload($game, $title);
              $this->message($msg);
            }
	    break;
          case "activate":
            if(isset($filename)){
              $player->rankingActivate($filename);
            }
            break;
          case 'deactivate':
            if(isset($filename)){
              $player->rankingDeactivate($filename);
            }
            break;
          case 'delete':
            if(isset($filename)){
              $player->rankingDelete($filename);
            }
            break;
          }
          $player->save();
        }
    }
    
    
    public function variables(){
      $vars = parent::variables();
      $player = $this->player();
      $vars['TICK_ICON']   = self::TICK_ICON;
      $vars['gameOptions'] = $this->gameOptions($vars['game'], "\t\t");
      unset($vars['game']);  // quick fix to prevent [game] change in record template
      return $vars;
    }


    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();

        $uploadList = new UploadList($html, 'upload');
        $vars['uploadList'] = $uploadList->make();
        return parent::make($vars); 
    }
    
}

?>
