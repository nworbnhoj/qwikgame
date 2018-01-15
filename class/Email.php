<?php

require_once 'Html.php';

/*******************************************************************************
    Class Email constructs an email by populating a html template with
    [variables] and {translations}.
*******************************************************************************/


class Email extends Html {

    static $headers;

    const EMAIL_TEMPLATE = "email";

    private $subject;
    private $body;
    private $to;



    /*******************************************************************************
    Class Email is constructed with an array containing relevent variables 
    including Subject, Message, To and any additional fields necessary to
    customize as required.

    $variables  array   containing required keys (subject, paragraphs & to) and
                        any optional key=>string to customize (eg day=>Saturday)
    $language   string  the 2 character language symbol (default: en = english)
    *******************************************************************************/

    public function __construct($variables=array(), $language='en'){
        parent::__construct($language);
        $this->to = $variables['to'];
        $this->subject = parent::make($variables['subject'], $variables);
        $template = $this->template(self::EMAIL_TEMPLATE);
        $this->body = parent::make($template, $variables);
    }


    public function make($html, $variables=array()){
        $html = $this->replicate($html, $variables['paragraphs']);
        $html = parent::make($html, $variables);
        return $html;
    }


    public function replicate($html, $paras){
        $tr = function($match) use ($paras){
            $id = $match[3];
            $html = $match[4];
            switch ($id) {
                case 'para': return $this->repPara($html, $paras);    break;
                default:     return '';           
            }
        };
        $pattern = "!(?s)\<repeat((\sid='(.+?)')|[^\>]*)\>(.+?)\<\/repeat\>!";
        return  preg_replace_callback($pattern, $tr, $html);
    }


    public function repPara($html, $paragraphs){
        $group = '';
        foreach($paragraphs as $para){
            $vars = array('para'=>$para);
            $group .= $this->populate($html, $vars);
        }
        return $group;
    }


    public function send(){
        $mail = mail(
            $this->to, 
            $this->subject, 
            $this->body, 
            self::$headers);
        if (!$mail){
            self::logMsg("The email was unable to be sent");
        }
    }


    // https://stackoverflow.com/questions/693691/how-to-initialize-static-variables
    static function initStatic(){
        $heads = array();
        $heads[] = "From: facilitator@qwikgame.org";
        $heads[] = "MIME-Version: 1.0";
        $heads[] = "Content-type: text/html; charset=UTF-8";
        self::$headers = implode("\r\n", $heads);
    }

}

Email::initStatic();

?>