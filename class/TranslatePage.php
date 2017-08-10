<?php

require_once 'qwik.php';
require_once 'Page.php';

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
    <script src='https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js'></script>
    <script src='qwik.js'></script>
</head>\n";

const TICK = "<button class='TICK_ICON'></button>";

const VENUE_INPUT = "
		<input id='venue-desc' 
    	    name='venue' 
    	    class='venue' 
    	    list='venue-squash'
			value='[venue]' 
    	    placeholder='{prompt_venue}' 
    	    size='120'
    	    required>
	";

const RIVAL_INPUT = "
        <input name='rival' 
            type='email' 
            placeholder='{prompt_rival}' 
            required>
	";
	
const REGION_SELECT = "
        <select name='region' class='region' required>
            <repeat id='reckon'>
                <option value='[region]'>[region]</option>
            </repeat>
        </select>
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
	




Class TranslatePage extends Page {

    private $langs;
    private $phraseKeys;
    private $files = array( 
        'index.html',
		'info.html',
		'locate.html',
        'player.html',
		'upload.html',
        'venue.html',
		'venues.html');
	
	private $variables;
	private $pending;



	public function __construct($template=null){
	    parent::__construct($template);
	    
        $this->langs = self::$translation->languages();
        $this->phraseKeys = self::$translation->phraseKeys();

	    $this->pending = new Translation('pending.xml', 'lang');
	
	    $gameOptions = $this->replicateGames(
		    "<option value='[game]' [selected]>[name]</option>",
		    array('game' => 'squash')
        );

        $selectGame = "<select name='game' class='game'>$gameOptions</select>";

        $vars = parent::variables();

        $vars['tick']         = "<a class='".self::TICK_ICON."'></a>";
        $vars['cross']        = "<a class='".self::CROSS_ICON."'></a>";
        $vars['termsLink']    = "<a href='".self::TERMS_URL."'>{terms & conditions}</a>";
        $vars['privacyLink']  = "<a href='".self::PRIVACY_URL."'>{privacy policy}</a>";
        $vars['flyerLink']    = "<a href='".self::FLYER_URL."'>{flyer}</a>";
        $vars['emailLink']    = self::EMAIL_LNK;
        $vars['facebookLink'] = self::FACEBOOK_LNK;
        $vars['twitterLink']  = self::TWITTER_LNK;
        $vars['inputVenue']   = VENUE_INPUT;
        $vars['inputRival']   = RIVAL_INPUT;
        $vars['selectGame']   = $selectGame;
        $vars['selectAbility']= ABILITY_SELECT;
        $vars['selectRegion'] = REGION_SELECT;
        $vars['selectParity3'] = PARITY3_SELECT;
        $vars['selectParity5'] = PARITY5_SELECT;
        
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


    public function html(){
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
        $html .= "<li>Some phrases include a variable such as [game] or [venue] that must be included in the translated phrase. So for example <i>'My favourite game is [game].'</i> might be translated as <i>'Mi juego favorito es [game].'</i>.</li>\n";
        $html .= "</ul>";
        $html .= "</p>";
        $count = count($this->langs) + 1;

        foreach($this->phraseKeys as $key){
            $size = strlen(self::$translation->phrase($key, 'en'));
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
                $phrase = self::$translation->phrase($key, $lang, '');
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
                $path = "lang/$lang/$file";
                $htm = file_get_contents("html/$file");
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
	    $phrase = self::$translation->phrase($key, $lang, '');
        $pending = $this->pending->phrase($key, $lang, '');
        $edit = is_null($pending) ? $phrase : $pending;
        $hidden = is_null($phrase) && is_null($pending) ? "" : "hidden";
        $submit = self::$translation->phrase('Submit', $lang);
        $dir = self::$translation->direction($lang);
        $rtl = ($dir === 'rtl') ? "dir='rtl' onkeyup='rtl(this)'" : '';

        $key = htmlentities($key, ENT_QUOTES | ENT_HTML5);
        $lang = htmlentities($lang, ENT_QUOTES | ENT_HTML5);
        $edit = htmlentities($edit, ENT_QUOTES | ENT_HTML5);
        $submit = htmlentities($submit, ENT_QUOTES | ENT_HTML5);

        $td  = "  <td>\n";
        $td .= "    <div class='phrase'>$phrase</div>\n";
        $td .= "    <div class='pending'>$pending</div>\n";
        $td .= "    <form action='translate.php#$key' method='post' class='edit-phrase' $hidden>\n";
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
