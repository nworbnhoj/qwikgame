<?php

require 'Page.php';

class InfoPage extends Page {

    public function __construct($templateName='info'){
        parent::__construct(NULL, $templateName);
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


    public function variables(){
        $vars = parent::variables();
        $vars['githubLink'] = self::GITHUB_LNK;
        return $vars;
    }


    private function qwikContact($msg, $from){
        $headers = array();
        $headers[] = "From: facilitator@qwikgame.org";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset=UTF-8";

        $to = 'feedback@qwikgame.org';
        $subject = "feedback from $from";

        if (mail($to, $subject, $msg, implode("\r\n", $headers))){
            $this->message('{thankyou_feedback}');
        } else {
            $this->alert("The feedback was unable to be sent.");
            Qwik::logMsg("Failed to send feebback email: $subject\n$msg");
        }
    }

}

?>
