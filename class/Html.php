<?php

require_once 'Qwik.php';
require_once 'PhraseBook.php';
require_once 'Venue.php';

/*******************************************************************************
    Class Html completes a html document by populating a html template with
    [variables] and {phrases}.

    The process begins with a html template; which is a file containing normal
    html supplimented by [variable] and {phrase} tags.

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
    followed by the {phrases}. This sequence implies that variables
    may contain {phrase} tags. For example: variable [day] may be
    replaced by value {saturday}; which is then translated (in spanish) to
    Sabado.

    [variables] are obtained by a call to Html::variables() which returns an
    array mapping variable=>value, both strings.

    {phrases} are contained in an xml file of the form:

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


    static $translationFileName = "translation.xml";
    static $phraseBook;


    // https://stackoverflow.com/questions/693691/how-to-initialize-static-variables
    static function initStatic(){
        self::$phraseBook = new PhraseBook(self::$translationFileName);
    }


    static public function readTemplate($name, $language='en'){
        if(empty($name)){
            return '';
        }

        $template = '';
        try{
            $template = file_get_contents(PATH_LANG."$language/$name.html");
        } catch (Throwable $t){
            Qwik::logThrown($t);
            $template = $this->errorHTML();
        } finally {
            return $template;
        }
    }


    const ERROR_TEMPLATE  = 'error';

    const PDF_URL    = QWIK_URL.PATH_PDF;
    const TERMS_URL  = self::PDF_URL.'qwikgame.org%20terms%20and%20conditions.pdf'; 
    const PRIVACY_URL  = self::PDF_URL.'qwikgame.org%20privacy%20policy.pdf';
    const TERMS_LNK    = "<a href='".self::TERMS_URL."' target='_blank'>{Terms and Conditions}</a>";
    const PRIVACY_LNK    = "<a href='".self::PRIVACY_URL."' target='_blank'>{Privacy_policy}</a>";

    private $template;
    private $language;

    /*******************************************************************************
    Class Html is constructed with an optional language.

    $language  the 2 character language symbol (eg en = english)
    *******************************************************************************/
    public function __construct($template='<html></html>', $language='en'){
        parent::__construct();
        $this->template = $template;
        $this->language = $language;
    }



    public function template($template=NULL){
        if(!is_null($template)){
            $this->template = $template;
        }
        return $this->template;
    }


    public function language($language=NULL){
        if(!is_null($language)){
            $this->language = $language;
        }
        return $this->language;
    }


    public function serve(){
        $html = "<html><head></head><body></body></html>";
        try{
            $html = $this->make();
        } catch (Throwable $t){
            Qwik::logThrown($t);
            $html = $this->errorHTML();
        } finally {
            echo($html);
        }
    }


    public function errorHTML(){
        $head = "<head><meta http-equiv='refresh' content='3' url='".QWIK_URL."' /></head>";
        $body = "<body><p>Opps! something went wrong.... <a href='".QWIK_URL."'>home</a></p></body>";
        $html = "<html>$head$body</html>";
        try{
            $html = $this->make(NULL, self::ERROR_TEMPLATE);
        } catch (Throwable $t){
            Qwik::logThrown($t);
        } 
        return $html;
    }


    public function variables(){
        return array(
            'homeURL'       => QWIK_URL,
            'termsURL'      => self::TERMS_URL,
            'privacyURL'    => self::PRIVACY_URL,
            'termsLink'     => self::TERMS_LNK,
            'privacyLink'   => self::PRIVACY_LNK,
        );
    }


    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();
        $html = $this->populate($html, $vars);
        $html = $this->translate($html);
        return $html;
    }



    /********************************************************************************
    Return the html template after replacing {phrases} with the requested
    language (or with the fallback language as required)

    $html    String    html template with variables of the form {name}
    $lang    String    language to replace {variables} with
    $fb      String    fallback language for when a translation is missing
    ********************************************************************************/
    public function translate($html, $lang=NULL, $fb='en'){
        $lang = is_null($lang) ? $this->language() : $lang;
        return self::$phraseBook->translate($html, $lang, $fb);
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


Html::initStatic();

?>
