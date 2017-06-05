<?php
	require 'qwik.php';


    static $langs = array('en', 'es', 'zh');
    static $files = array( 
        'index.html',
		'info.html',
		'locate.html',
        'player.html',
		'upload.html',
        'venue.html',
		'venues.html');


	$tick = "<button class='TICK_ICON'></button>";


	$inputVenue = "
		<input id='venue-desc' 
    	    name='venue' 
    	    class='venue' 
    	    list='venue-squash'
			value='<v>venue</v>' 
    	    placeholder='<t>prompt_venue</t>' 
    	    size='120'
    	    required>
	";


	$inputRival = "
        <input name='rival' 
            type='email' 
            placeholder='<t>prompt_rival</t>' 
            required>
	";

    $selectRegion = "
        <select name='region' class='region' required>
            <repeat id='reckon'>
                <option value='<v>region</v>'><v>region</v></option>
            </repeat>
        </select>
    ";


    $selectAbility = "
        <select name='ability' required>
            <option value='4'><t>very_strong</t></option>
            <option value='3'><t>strong</t></option>
            <option value='2' selected><t>competent</t></option>
            <option value='1'><t>weak</t></option>
            <option value='0'><t>very_weak</t></option>
        </select>
    ";

	$selectParity3 = "
    	<select name='parity'>
    	    <option value='matching' disabled><t>matching</t></option>
    	    <option value='similar' selected><t>similar</t></option>
    	    <option value='any'><t>any</t></option>
    	</select>
	";

	$selectParity5 = "
		<select name='parity'>
          <option value='+2'><t>much_stronger</t></option>
          <option value='+1'><t>stronger</t></option>
          <option value='0' selected><t>well_matched</t></option>
          <option value='-1'><t>weaker</t></option>
          <option value='-2'><t>much_weaker</t></option>
        </select>
	";


	
	$gameOptions = replicateGames(
		"<option value='<v>game</v>' <v>selected</v>><v>name</v></option>",
		array('game' => 'squash')
	);

    $selectGame = "<select name='game' class='game'>$gameOptions</select>";

    $variables = array(
		'tick'				=> "<a class='$TICK_ICON'></a>",
		'cross'				=> "<a class='$CROSS_ICON'></a>",
		'termsLink'			=> "<a href='$termsURL'><t>terms & conditions</t></a>",
        'privacyLink'		=> "<a href='$privacyURL'><t>privacy policy</t></a>",
        'flyerLink'         => "<a href='$flyerURL'><t>flyer</t></a>",
        'emailLink'         => $emailLink,
        'facebookLink'      => $facebookLink,
        'twitterLink'       => $twitterLink,
		'inputVenue'		=> $inputVenue,
		'inputRival'		=> $inputRival,
        'selectGame'        => $selectGame,
        'selectAbility'     => $selectAbility,
		'selectRegion'		=> $selectRegion,
		'selectParity3'		=> $selectParity3,
		'selectParity5'		=> $selectParity5
    );




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

    foreach($langs as $lang){
		echo "<h3>$lang translation</h3>";
        foreach($files as $file){
            $path = "lang/$lang/$file";
			echo "<br><a href='$path'>$file</a>";
            $html = file_get_contents("html/$file");
            $html = translate($html, $lang);			// sentences with differing word order
			$html = populate($html, $variables);		// select elements in sentences
			$html = translate($html, $lang);			// translate all remaining
            file_put_contents($path, $html, LOCK_EX);
        }
		echo "<br>";
    }

echo "<hr><h2>Missing translations</h2>\n";
	$english = $GLOBALS['en'];
	foreach($langs as $lang){
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
	$count = count($langs);
    $english = $GLOBALS['en'];
    $html = '<hr>';
    $html .= "<table style='width:100%'>";
    foreach($english as $key => $eng){
        $html .= "<tr><td rowspan='$count'>$key</td><td>en</td><td>$eng</td></tr>";
	    foreach($langs as $lang){
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




?>
