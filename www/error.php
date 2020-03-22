<!DOCTYPE html PUBLIC "-//W2C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php

require_once 'up.php';
require_once UP.PATH_CLASS.'Qwik.php';

	$msg = '';       
 
        if(isset($_GET['msg'])){
                $msg = $_GET['msg'];
        }

        
?>



<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta charset="UTF-8">	
 <link href='https://fonts.googleapis.com/css?family=Pontano+Sans' rel='stylesheet' type='text/css'>
<link rel="stylesheet" type="text/css" href="qwik.css">
</head>

<body>
<h1>qwik game org</h1>

<h2>Game Over</h2>

<p>Opps! something went wrong.</p>

<p><?php echo $msg ?>

<p><a href=index.php>home</a></p>


<br><br><br><br><br><br><br>

<?php echo $cc ?>


</body>

</html>
