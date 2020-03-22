<!DOCTYPE html PUBLIC "-//W2C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php

require_once 'up.php';
require_once UP.PATH_CLASS.'Qwik.php';

    $req = validate($_POST);
    if (!$req){
        $req = validate($_GET);
    }

//echo "<br><br>";
//print_r($req);
//echo "<br><br>";


    $game = $req['game'];
    $venueID = $req['id'];
    $venue=readVenueXML($venueID, $game);

?>


<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics</title>
    <link href='https://fonts.googleapis.com/css?family=Pontano+Sans' rel='stylesheet' type='text/css'>
    <link href="//netdna.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="qwik.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="qwik.js"></script>
</head>

<body>


<h1>qwik game org</h1>
<h2>Statistics</h2>


<h3>Players</h3>
<table class='center wide'>
<tr>
    <th>id</th>
    <th>name</th>
    <th>debut</th>
    <th>rep</th>
    <th>available</th>
    <th>confirmed</th>
    <th>feedback</th>
    <th>history</th>
</tr>

<?php
    $playerTotal = 0;
    $playerActive = 0;
    $pids = pids();
    $rows = array();
    foreach($pids as $pid){
        $row = '<tr>';
        $playerTotal += 1;
        $player = new Player($pid, $log);
        if(null !== $player->email()){
            $playerActive += 1;
            $available = $player->available();
            $matchConf = $player->matchStatus("confirmed");
            $matchFeed = $player->matchStatus("feedback");
            $matchHist = $player->matchStatus("history");
            $avaiCount = count($available);
            $confCount = count($matchConf);
            $feedCount = count($matchFeed);
            $histCount = count($matchHist);
            $avaiCount = $avaiCount == 0 ? '' : $avaiCount;
            $confCount = $confCount == 0 ? '' : $confCount;
            $feedCount = $feedCount == 0 ? '' : $feedCount;
            $histCount = $histCount == 0 ? '' : $histCount;
            $row .= "<td>" . substr($pid, 0, 4) . "</td>";
            $row .= "<td>" . $player->nick() . "</td>";
            $row .= "<td>" . $player->debut() . "</td>";
            $row .= "<td>" . repWord($player) . "</td>";
            $row .= "<td>$avaiCount</td>";
            $row .= "<td>$confCount</td>";
            $row .= "<td>$feedCount</td>";
            $row .= "<td>$histCount</td>";
        }
        $row .= "</tr>\n";
        $index = sprintf('%04d' , $histCount);
        $index .= ' ' . sprintf('%04d', $avaiCount);
        $index .= ' ' . $player->id();
        $rows[$index] = $row;
    }

    krsort($rows);

    $rows[] = "<tr><th>active players</th><td>$playerActive</td></tr>";
    $rows[] = "<tr><th>total players</th><td>$playerTotal</td></tr>";

    foreach($rows as $row){
        echo $row;
    }

?>
</table>




<br><br><br><br><br><br>

<h3>Venues</h3>
<table class='center wide'>
<tr>
    <th>players</th>
    <th></th>
    <th>venue</th>
    <th></th>
    <th>lat</th>
    <th>lng</th>
    <th>url</th>
    <th>phone</th>
    <th>ids</th>
</tr>

<?php
    $vids = venues();
    $rows = array();
    foreach($vids as $vid){
        $venue = readVenueXML($vid);
        if (isset($venue)){
            $svid = Venue::svid($vid);
            $players = $venue->xpath("player");
            $playerCount = count($players);
            $ids='';
            foreach($players as $pid){
                $ids .= substr($pid, 0, 4) . ' ';
            }
            $lat = isset($venue['lat']) ? '✔' : '';
            $lng = isset($venue['lng']) ? '✔' : '';
            $url = $venue['url'];
            $url = isset($url) ? "<a href='$url'>url</a>" : '';
            $phone = isset($venue['phone']) ? '✔' : '';

            $index = sprintf('%04d' , $playerCount);

            $name = $venue['name'];

            $row = "<tr>";
            $row .= "<td>$playerCount</td>";
            $row .= "<td><a href='venue.php?vid=$vid'>$name</a></td>";
            $row .= "<td>" . $venue['locality'] . "</td>";
            $row .= "<td>" . $venue['country'] . "</td>";
            $row .= "<td>$lat</td>";
            $row .= "<td>$lng</td>";
            $row .= "<td>$url</td>";
            $row .= "<td>$phone</td>";
            $row .= "<td>$ids</td>";

            $row .= "</tr>\n";
            $rows["$index $svid"] = $row;
        }
    }
    krsort($rows);
    foreach($rows as $row){
        echo $row;
    }
?>
</table>






<br><br><br><br><br><br><br>

<?php echo $cc ?>


</body>

</html>


