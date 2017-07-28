<?php

require_once 'qwik.php';
require_once 'Page.php';

const TICK = "<button class='TICK_ICON'></button>";

const VENUE_INPUT = "
		<input id='venue-desc' 
    	    name='venue' 
    	    class='venue' 
    	    list='venue-squash'
			value='<v>venue</v>' 
    	    placeholder='<t>prompt_venue</t>' 
    	    size='120'
    	    required>
	";

const RIVAL_INPUT = "
        <input name='rival' 
            type='email' 
            placeholder='<t>prompt_rival</t>' 
            required>
	";
	
const REGION_SELECT = "
        <select name='region' class='region' required>
            <repeat id='reckon'>
                <option value='<v>region</v>'><v>region</v></option>
            </repeat>
        </select>
    ";

const ABILITY_SELECT = "
        <select name='ability' required>
            <option value='4'><t>very_strong</t></option>
            <option value='3'><t>strong</t></option>
            <option value='2' selected><t>competent</t></option>
            <option value='1'><t>weak</t></option>
            <option value='0'><t>very_weak</t></option>
        </select>
    ";

const PARITY3_SELECT  = "
    	<select name='parity'>
    	    <option value='matching' disabled><t>matching</t></option>
    	    <option value='similar' selected><t>similar</t></option>
    	    <option value='any'><t>any</t></option>
    	</select>
	";

const PARITY5_SELECT = "
		<select name='parity'>
          <option value='+2'><t>much_stronger</t></option>
          <option value='+1'><t>stronger</t></option>
          <option value='0' selected><t>well_matched</t></option>
          <option value='-1'><t>weaker</t></option>
          <option value='-2'><t>much_weaker</t></option>
        </select>
	";
	




Class TranslatePage extends Page {

    private $langs = array('en', 'es', 'zh');
    private $files = array( 
        'index.html',
		'info.html',
		'locate.html',
        'player.html',
		'upload.html',
        'venue.html',
		'venues.html');
	
	private $variables;


	
	public function __construct($template=null){
	    parent::__construct($template);
	
	    $gameOptions = $this->replicateGames(
		    "<option value='<v>game</v>' <v>selected</v>><v>name</v></option>",
		    array('game' => 'squash')
        );

        $selectGame = "<select name='game' class='game'>$gameOptions</select>";

        $vars = parent::variables();

        $vars['tick']         = "<a class='".TICK_ICON."'></a>";
        $vars['cross']        = "<a class='".CROSS_ICON."'></a>";
        $vars['termsLink']    = "<a href='".TERMS_URL."'><t>terms & conditions</t></a>";
        $vars['privacyLink']  = "<a href='".PRIVACY_URL."'><t>privacy policy</t></a>";
        $vars['flyerLink']    = "<a href='".FLYER_URL."'><t>flyer</t></a>";
        $vars['emailLink']    = EMAIL_LNK;
        $vars['facebookLink'] = FACEBOOK_LNK;
        $vars['twitterLink']  = TWITTER_LNK;
        $vars['inputVenue']   = VENUE_INPUT;
        $vars['inputRival']   = RIVAL_INPUT;
        $vars['selectGame']   = $selectGame;
        $vars['selectAbility']= ABILITY_SELECT;
        $vars['selectRegion'] = REGION_SELECT;
        $vars['selectParity3'] = PARITY3_SELECT;
        $vars['selectParity5'] = PARITY5_SELECT;
        
        $this->variables = $vars;
    }
    
    
    public function serve(){

    	echo "
    		<head>
    			<style>
    				table {width:100%; border:1px solid black; border-collapse: collapse;}
                    td {height:auto; border:1px solid black;}
    				tr:nth-child(odd) {background-color:LightGrey;}
    
    			</style>
    
    		</head>
    		<body>
    	";

        foreach($this->langs as $lang){
    		echo "<h3>$lang translation</h3>";
            foreach($this->files as $file){
                $path = "lang/$lang/$file";
    			echo "<br><a href='$path'>$file</a>";
                $html = file_get_contents("html/$file");
                $html = $this->translate($html, $lang);   // sentences with differing word order
    			$html = $this->populate($html, $this->variables); // select elements
    			$html = $this->translate($html, $lang);     // translate all remaining
                file_put_contents($path, $html, LOCK_EX);
            }
    		echo "<br>";
        }

        echo "<hr><h2>Missing translations</h2>\n";
    	$english = $GLOBALS['en'];
    	foreach($this->langs as $lang){
    		$strings = $GLOBALS[$lang];
    
    		$html = '<hr>';
    		$html .= "<table style='width:100%'>";
    		foreach($english as $key => $eng){
    			if (!isset($strings[$key])){
    		        $html .= "<tr><td>$key</td><td>$lang</td><td>$eng</td></tr>";
    			}
    		}
    
    		$html .= '</table>';
    		$html .= "<hr>\n";
    		echo "$html";
    
    	}
        echo "</body></html>";


        echo "<hr><h2>Full translations</h2>\n";
    	$count = count($this->langs);
        $english = $GLOBALS['en'];
        $html = '<hr>';
        $html .= "<table style='width:100%'>";
        foreach($english as $key => $eng){
            $html .= "<tr><td rowspan='$count'>$key</td><td>en</td><td>$eng</td></tr>";
    	    foreach($this->langs as $lang){
    			if($lang != 'en'){
    	        	$str = $GLOBALS[$lang][$key];
                	$html .= "<tr><td>$lang</td><td>$str</td></tr>";
    			}
    		}
        }
    
        $html .= '</table>';
        $html .= "<hr>\n";
        echo "$html";
    
    	echo "</body></html>";
	}

}


?>
