<?php

require_once 'Qwik.php';
require_once 'Translation.php';
require_once 'Venue.php';

/*******************************************************************************
    Class Html completes a html document by populating a html template with
    [variables] and {translations}.

    The process begins with a html template; which is a file containing normal
    html supplimented by [variable] and {translation} tags.

        <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <meta charset="UTF-8">
            </head>
            <body>
                <h1>{hello}</h1>
                <p>You have a game of [game] on [day] at [time]</p>
            </body>
        </html>

    A call to Html::make() first populates the [variables] and this is 
    followed by the {translations}. This sequence implies that variables
    may contain {translation} tags. For example: variable [day] may be
    replaced by value {saturday}; which is then translated (in spanish) to
    Sabado.

    [variables] are obtained by a call to Html::variables() which returns an
    array mapping variable=>value, both strings.

    {translations} are contained in an xml file of the form:

        <?xml version="1.0"?>
        <translation>
            <language key="en" dir="ltr">English</language>
            <language key="es" dir="ltr">Espa&#xF1;ol</language>
            <phrase key="hello">
                <en>Hello</en>
                <es>Hola</es>
            </phrase>
        </translation>

*******************************************************************************/

class Html extends Qwik {

    static $translation;

    const ERROR_TEMPLATE  = 'error.html';

    const QWIK_URL   = 'http://' . self::SUBDOMAIN . '.qwikgame.org';
    const PDF_URL    = self::QWIK_URL.'/'.self::PATH_PDF.'/';
    const TERMS_URL  = self::PDF_URL.'qwikgame.org%20terms%20and%20conditions.pdf'; 
    const PRIVACY_URL  = self::PDF_URL.'qwikgame.org%20privacy%20policy.pdf';
    const TERMS_LNK    = "<a href='".self::TERMS_URL."' target='_blank'>{Terms and Conditions}</a>";
    const PRIVACY_LNK    = "<a href='".self::PRIVACY_URL."' target='_blank'>{Privacy_policy}</a>";

    private $templateName;
    private $language;
    private $req;

    /*******************************************************************************
    Class Html is constructed with an optional language.

    $language  the 2 character language symbol (eg en = english)
    *******************************************************************************/
    public function __construct($templateName='index', $language='en'){
        parent::__construct();
        $this->templateName = $templateName;
        $this->language = $language;
    }


    public function language($language=NULL){
        if(!is_null($language)){
            $this->language = $language;
        }
        return $this->language;
    }


    /**
     * This is a caching function that ensures the file translation.xml is only read once.
     * Be sure to use &reference when wishing to make changes and call .save() 
     */
    function &translation(){
        if (is_null(self::$translation)){
            self::$translation = new Translation('translation.xml');
        }
        return self::$translation;
    }


    public function serve(){
        $html = "<html><head></head><body></body></html>";
        try{
            $templateName = $this->templateName;
            $template = $this->template($templateName);
            $html = $this->make($template, $this->variables());
        } catch (Throwable $t){
            Qwik::logThrown($t);
            $html = errorHTML();
        } finally {
            echo($html);
        }
    }


    private function errorHTML(){
        $home = self::QWIK_URL;
        $html = "<html><head><meta http-equiv='refresh' content='3;url={$home}' /></head><body><p>Opps! something went wrong.... <a href='{$home}'>home</a></p></body></html>";
        try{
            $html = $this->make(self::ERROR_TEMPLATE, $this->variables);
        } catch (Throwable $t){
            Qwik::logThrown($t);
        } 
        return $html;
    }


    public function variables(){
        return array(
            'homeURL'       => self::QWIK_URL,
            'termsURL'      => self::TERMS_URL,
            'privacyURL'    => self::PRIVACY_URL,
            'termsLink'     => self::TERMS_LNK,
            'privacyLink'   => self::PRIVACY_LNK,
        );
    }


    protected function template($templateName){
        $template = '';
        if(!empty($templateName)){
            $PATH = Qwik::PATH_LANG.'/'.$this->language();
            $template = file_get_contents("$PATH/$templateName.html");
        }
        return $template;
    }


    public function make($html, $variables=array()){
        $vars = array_merge($this->variables(), $variables);
        $html = $this->populate($html, $vars);
        $html = $this->translate($html, $this->language());
        return $html;
    }



    /********************************************************************************
    Return the html template after replacing {variables} with the requested
    language (or with the fallback language as required)

    $html    String    html template with variables of the form {name}
    $lang    String    language to replace {variables} with
    $fb      String    fallback language for when a translation is missing
    ********************************************************************************/
    public function translate($html, $lang, $fb='en'){
        $translation = self::translation();
        $pattern = '!(?s)\{([^\}]+)\}!';
        $tr = function($match) use ($translation, $lang, $fb){
            $key = $match[1];
            $phrase = $translation->phrase($key, $lang, $fb);
            return empty($phrase) ? '{'."$key".'}' : $phrase;
        };
        return  preg_replace_callback($pattern, $tr, $html);
    }


    /********************************************************************************
    Return the html template after replacing [variables] with the values provided.

    $html        String        html template with variables of the form [key]
    $variables    ArrayMap    variable name => $value
    ********************************************************************************/
    public function populate($html, $variables){
        $pattern = '!(?s)\[([^\]]+)\]!';
        $tr = function($match) use ($variables){
            $m = $match[1];
            return isset($variables[$m]) ? $variables[$m] : "[$m]";
        };
        return  preg_replace_callback($pattern, $tr, $html);
    }

}

?>
