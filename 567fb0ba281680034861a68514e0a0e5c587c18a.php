<!DOCTYPE html PUBLIC "-//W2C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
	require 'qwik.php';
?>


<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta charset="UTF-8">
	<title>retrofit data for git commit 149376a7e134862514b0773d76d76b6fc2a2f3dd</title>
</head>

<body>


<h1>qwik game org</h1>
<h2>retrofit data for git commit 567fb0ba281680034861a68514e0a0e5c587c18a</h2>


<h3>Players</h3>

<?php
	$pids = pids();
	$rows = array();
	foreach($pids as $pid){
		$player = readPlayerXML($pid);
		if(isset($player)){
		    print_r($player['id']);
            $reckons = $player->xpath("reckon");
            foreach ($reckons as $reckon) {
                if(isset($reckon['time'])) {
            
                    print_r($match['id']);
            
                    $date = create_date($reckon['time']);
                    $reckon['date'] = $date->format("d-m-Y");
                    
                    removeAtt($reckon, 'time');
                } 
            }
            writePlayerXML($player); 
        }
	}


?>

</body>

</html>


