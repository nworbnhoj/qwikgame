<?php

require_once 'Page.php';


class UploadPage extends Page {

    public function __construct($template='upload'){
        parent::__construct($template);
    }
	
	
    public function processRequest(){

        $player = $this->player();
        $qwik = $this->req('qwik');
        $game = $this->req('game');
        $title = $this->req('title');
        $filename = $this->req('filename');

	if (isset($player) && isset($qwik)){
            $qwik = $this->req('qwik');

            switch ($qwik) {
                case 'upload':
                    if(isset($game) && isset($title)){
                        $player->rankingUpload($game, $title);
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
        $variables = parent::variables();
        $player = $this->player();

        $variables['please_login']   = $player ? '' : '{prompt_login}';
        $variables['uploadHidden']   = $player ? '' : 'hidden';
        $variables['uploadDisabled'] = $player ? '' : 'disabled';
        $variables['TICK_ICON']      = self::TICK_ICON;
        $variables['LOGOUT_ICON']    = isset($player) ? self::LOGOUT_ICON : '';

        return $variables;
    }
    
}

?>
