<?php

require 'Page.php';

class InfoPage extends Page {

    public function __construct($templateName='info'){
        parent::__construct(Html::readTemplate($templateName), $templateName);
    }


    public function processRequest(){
        $qwik = $this->req('qwik');
        switch ($qwik){
            case 'feedback':
                $this->qwikContact($this->req('message'), $this->req('reply-email'));
                break;
		    default:
	    }
	}


    private function qwikContact($msg, $from){
        $headers = array();
        $headers[] = "From: $from";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset=UTF-8";

    //    $to = 'facilitator@qwikgame.org';
        $to = 'john@nhoj.info';
        $subject = "feedback from $from";

        if (! mail($to, $subject, $msg, implode("\r\n", $headers))){
            header("Location: error.php?msg=<b>The email was unable to be sent");
        }
    }

}

?>
