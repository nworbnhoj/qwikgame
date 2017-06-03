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
<h2>retrofit data for git commit 149376a7e134862514b0773d76d76b6fc2a2f3dd</h2>


<h3>Players</h3>

<?php
    $pids = pids();
    foreach($pids as $pid){
        $player = readPlayerXML($pid);
        if(isset($player)){
            echo "$pid ";
            $matchHist = $player->xpath("match[@status='history']");
            foreach ($matchHist as $match) {
                if(isset($match['rep'])
                & isset($match['parity'])) {
                    $mid = $match['id'];
                    echo "$mid ";

                    $request = array();
                    $request['id'] = (string)$match['id'];
                    $request['rep'] = (string)$match['rep'];
                    $request['parity'] = (string)$match['parity'];

                    removeAtt($match, 'rep');
                    removeAtt($match, 'parity');
                    removeAtt($match, 'rely');

                    qwikFeedback($player, $request);
                }
            }
            echo "<br>";
        }
    }


?>


</body>

</html>


