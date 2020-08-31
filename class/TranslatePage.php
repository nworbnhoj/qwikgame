<?php

require_once 'Html.php';
require_once 'Page.php';
require_once 'Translation.php';

const HEAD = "
<!DOCTYPE html PUBLIC '-//W2C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>

<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
    <meta charset='UTF-8'>
    <style>
        table {width:100%; border:1px solid black; border-collapse: collapse;}
        td {height:auto; border:1px solid black;}
        tr:nth-child(odd) {background-color:LightGrey;}
        .pending {color:DarkGreen;}
    </style>
    <script src='qwik.js'></script>
    <script src='translate.js'></script>
</head>\n";

const TICK = "<button class='TICK_ICON'></button>";

const VENUE_SELECT = "
        <select id='venue-select' name='vid' value='[value]' required>
            <option id='venue-prompt' disabled selected>{prompt_venue}</option>
            <option id='venue-from-map' value='show-map'>{select_from_map}</option>
            <optgroup id='venue-favorite' class='json' label='{favorite}'>
            </optgroup>
            <optgroup id='venue-other' class='json' label='{other}'>
            </optgroup>
        </select>
    ";

const RIVAL_INPUT = "
        <input name='rival' 
            type='email' 
            placeholder='{prompt_rival}' 
            required>
    ";

const ABILITY_SELECT = "
        <select name='ability' required>
            <option value='4'>{very_strong}</option>
            <option value='3'>{strong}</option>
            <option value='2' selected>{competent}</option>
            <option value='1'>{weak}</option>
            <option value='0'>{very_weak}</option>
        </select>
    ";

const PARITY3_SELECT  = "
        <select name='parity'>
            <option value='matching' disabled>{matching}</option>
            <option value='similar' selected>{similar}</option>
            <option value='any'>{any}</option>
        </select>
    ";

const PARITY5_SELECT = "
        <select name='parity'>
          <option value='+2'>{much_stronger}</option>
          <option value='+1'>{stronger}</option>
          <option value='0' selected>{well_matched}</option>
          <option value='-1'>{weaker}</option>
          <option value='-2'>{much_weaker}</option>
        </select>
    ";

const BUTTON_THUMB = "<button type='button' class='" . Page::THUMB_UP_ICON . "'></button>";
    


Class TranslatePage extends Html {

    private $translation;
    private $pending;
    private $langs;
    private $phraseKeys;
    private $files = array(
        'admin.html',
        'account.html',
        'email.html',
        'favorite.html',
        'friend.html',
        'index.html',
        'info.html',
        'match.html',
        'upload.html',
        'offline.html');
    private $variables;

    public function __construct($templateName=NULL){
        $template = Html::readTemplate($templateName);
        parent::__construct($template);
        
        $this->translation = new Translation(self::$translationFileName);
        $this->pending = new Translation('pending.xml');

        $this->langs = parent::$phraseBook->languages();
        $this->phraseKeys = parent::$phraseBook->phraseKeys();

        $selectGame = "<select name='game' class='game select-game'>[gameOptions]</select>";
        $selectRegion = "<select id='region' name='region' class='json region' required>[regionOptions]</select>";

        $vars = parent::variables();
        $vars['ACCOUNT_ICON']  = Page::ACCOUNT_ICON;
        $vars['CROSS_ICON']    = Page::CROSS_ICON;
        $vars['COMMENT_ICON']  = Page::COMMENT_ICON;
        $vars['EMAIL_ICON']    = Page::EMAIL_ICON;
        $vars['FACEBOOK_ICON'] = Page::FACEBOOK_ICON;
        $vars['FAVORITE_ICON'] = Page::FAVORITE_ICON;
        $vars['FRIEND_ICON']   = Page::FRIEND_ICON;
        $vars['FEMALE_ICON']   = Page::FEMALE_ICON;
        $vars['HELP_ICON']     = Page::HELP_ICON;
        $vars['HOME_ICON']     = Page::HOME_ICON;
        $vars['INFO_ICON']     = Page::INFO_ICON;
        $vars['LANG_ICON']     = Page::LANG_ICON;
        $vars['MALE_ICON']     = Page::MALE_ICON;
        $vars['MATCH_ICON']    = Page::MATCH_ICON;
        $vars['RELOAD_ICON']   = Page::RELOAD_ICON;
        $vars['THUMB_DN_ICON'] = Page::THUMB_DN_ICON;
        $vars['THUMB_UP_ICON'] = Page::THUMB_UP_ICON;
        $vars['TICK_ICON']     = Page::TICK_ICON;
        $vars['TWITTER_ICON']  = Page::TWITTER_ICON;
        $vars['GITHUB_ICON']   = Page::GITHUB_ICON;
        $vars['tick']          = "<a class='".Page::TICK_ICON."'></a>";
        $vars['cross']         = "<a class='".Page::CROSS_ICON."'></a>";
        $vars['emailLink']     = Page::EMAIL_LNK;
        $vars['facebookLink']  = Page::FACEBOOK_LNK;
        $vars['tweetLink']     = Page::TWEET_LNK;
        $vars['forumLink']     = Page::FORUM_LNK;
        $vars['twitterLink']   = Page::TWITTER_LNK;
        $vars['githubLink']    = Page::GITHUB_LNK;
        $vars['translateLink'] = Page::TRANSLATE_LNK;
        $vars['selectVenue']   = VENUE_SELECT;
        $vars['inputRival']    = RIVAL_INPUT;
        $vars['selectGame']    = $selectGame;
        $vars['selectAbility'] = ABILITY_SELECT;
        $vars['selectRegion']  = $selectRegion;
        $vars['parity3Select'] = PARITY3_SELECT;
        $vars['parity5Select'] = PARITY5_SELECT;
        $vars['thumbButton']   = BUTTON_THUMB;
        
        $this->variables = $vars;
    }
    

    public function processRequest(){
        $key = $this->req('key');
        $lang = $this->req('lang');
        $phrase = $this->req('phrase');
        if (!is_null($key) && !is_null($lang) && !is_null($phrase)){
            $this->pending->set($key, $lang, $phrase);
        }
        $this->pending->save();
    }


    public function make($variables=NULL, $html=NULL){
        $html = is_null($html) ? $this->template() : $html;
        $vars = is_array($variables) ? array_merge($this->variables(), $variables) : $this->variables();
        $html = HEAD;
        $html .= "<body>\n";
        $html .= $this->translateTemplates();
        $html .= "<br><br><br>\n";
        $html .= "<h2>Edit Translations</h2>\n";
        $html .= "<p>";
        $html .= "Thank you for helping to translate qwikgame into your language.\n";
        $html .= "<ul>";
        $html .= "<li>Click on an existing translation to correct it.</li>\n";
        $html .= "<li>When you {Submit} a phrase it will be displayed in <span class='pending'>green</span> until it is accepted.</li>\n";
        $html .= "<li>Some phrases include a variable such as [game] or [venue] that must be included in the translated phrase. So for example <i>'My favorite game is [game].'</i> might be translated as <i>'Mi juego favorito es [game].'</i>.</li>\n";
        $html .= "</ul>";
        $html .= "</p>";
        $count = count($this->langs) + 1;

        foreach($this->phraseKeys as $key){
            $size = strlen(self::$phraseBook->phrase($key, 'en'));
            $size = $size > 100 ? 100 : $size;
            $html .= "<table style='width:100%' id='$key'>\n";
            $html .= "<tr>";
            $html .= "  <td colspan='2'><b>$key</b></td>";
            $html .= "</tr>\n";
            foreach($this->langs as $lang => $native){
                $html .= "<tr>\n";
                $html .= "  <td style='width:10%'>$native</td>\n";
                $html .= $this->tdPhrase($key, $lang, $size);
                $html .= "</tr>\n";
            }
            $html .= "</table>\n";
            $html .= "<br><br>\n";
        }
        $html .= "<hr>\n";
        $html .= "</body>\n</html>\n";
        return $html;
    }


    private function stats(){
        $count = array();
        foreach($this->langs as $lang => $native){
            $count[$lang] = 0;
        }

        foreach($this->phraseKeys as $key){
            foreach($this->langs as $lang => $native){
                $phrase = self::$phraseBook->phrase($key, $lang, '');
                if (!empty($phrase)){
                    $count[$lang] += 1;
                }
            }
        }

        $stats = array();
//        $denominator = count($this->phraseKeys);
        $denominator = $count['en'];
        foreach($count as $lang => $numerator){
            $stats[$lang] = intval(100 * $numerator / $denominator);
        }
        return $stats;
    }


    private function translateTemplates(){
        $stats = $this->stats();
        $html = "<h2>Translations</h2>\n";
        $html .= "<table>\n";
        foreach($this->langs as $lang => $native){
            $stat = $stats[$lang];
            $html .= "<tr><td><big><b>$native</b></big></td>\n";
            $html .= "<td>$stat%</td>\n";
            foreach($this->files as $file){
                $path = PATH_LANG."$lang/$file";
                $htm = file_get_contents(PATH_HTML."$file");
                $htm = $this->translate($htm, $lang);   // sentences with differing word order
                $htm = $this->populate($htm, $this->variables); // select elements
                $htm = $this->translate($htm, $lang);     // translate all remaining
                file_put_contents($path, $htm, LOCK_EX);
                $html .= "<td><a href='$path'>$file</a></td>\n";
            }
            $html .= "</tr>\n";
        }
        $html .= "</table>\n";
        return $html;
    }


    private function tdPhrase($key, $lang, $size=30){
        $phrase = self::$phraseBook->phrase($key, $lang, '');
        $pending = $this->pending->phrase($key, $lang, '');
        $edit = is_null($pending) ? $phrase : $pending;
        $hidden = is_null($phrase) && is_null($pending) ? "" : "hidden";
        $submit = self::$phraseBook->phrase('Submit', $lang);
        $dir = self::$phraseBook->direction($lang);
        $rtl = ($dir === 'rtl') ? "dir='rtl' onkeyup='rtl(this)'" : '';

        $key = htmlentities($key, ENT_QUOTES | ENT_HTML5);
        $lang = htmlentities($lang, ENT_QUOTES | ENT_HTML5);
        $edit = htmlentities($edit, ENT_QUOTES | ENT_HTML5);
        $submit = htmlentities($submit, ENT_QUOTES | ENT_HTML5);

        $td  = "  <td>\n";
        $td .= "    <div class='phrase'>$phrase</div>\n";
        $td .= "    <div class='pending'>$pending</div>\n";
        $td .= "    <form action='translate.php#$key' method='post' class='edit-phrase' $hidden>\n";
        $td .= "      <input type='hidden' name='qwik' value='translate'>\n";
        $td .= "      <input type='hidden' name='key' value='$key'>\n";
        $td .= "      <input type='hidden' name='lang' value='$lang'>\n";
        $td .= "      <input type='text' name='phrase' value='$edit' size='$size' $rtl>\n";
        $td .= "      <input type='submit' value='$submit'>\n";
        $td .= "    </form>\n";
        $td .= "  </td>\n";

        return $td;
    }

}


?>
