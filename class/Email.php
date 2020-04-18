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

    public function __construct($variables=array(), $language='en', $templateName=self::EMAIL_TEMPLATE){
        parent::__construct(Html::readTemplate($templateName, $language), $language, $templateName);
        $this->to = $variables['to'];
        $subject = $variables['subject'];
        $this->subject = $this->make($variables, $subject);
        $body = $this->template();
        $this->body = $this->make($variables, $body);
    }


    public function toString(){
        $str = "to:\t".$this->to."\n";
        $str .= "subject:\t".$this->subject."\n";
        $str .= $this->body;
        return $str;
    }


    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();
        $html = $this->replicate($html, $vars['paragraphs']);
        $html = parent::make($vars, $html);
        $html = parent::make($vars, $html);   //double pass necessary
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
            $errorMessage = error_get_last()['message'];
            self::logMsg("The email was unable to be sent\n$errorMessage");
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
