<?php

// Salvo i parametri in variabili
$q = $_GET['q'] ;
$p = $_GET['p'] ;
$cc = $_GET['cc'];
$control = $_GET['check'];

header('Access-Control-Allow-Origin: *');

// Connetto al db
$con = mysqli_connect('mysql.cpaservice.it','cpa_admin','5Kdjoy%*6Gv*','cpaservice');

if (!$con) {
  die('Could not connect: ' . mysqli_error($con));
}

if($q != ''){
	date_default_timezone_set("Europe/Rome");
	$dataToLog = array(
		date("Y-m-d H:i:s"), //Date and time,
		$cc ?? '',	
		$_SERVER['REMOTE_ADDR'], //IP address
		$q,

	);
	$data = implode(" - ", $dataToLog);
	$data .= PHP_EOL;
	$pathToFile = '/home/dh_7r8stv/mila.cpaservice.it/emailInserted.log';
	file_put_contents($pathToFile, $data, FILE_APPEND);
}


// controllo se esistono record con i parametri passati
if($_GET['q'] != ''){
	switch($control){
		case 'campaign':
		$sql="SELECT * FROM clients WHERE email = '$q' AND updated_at > '2022-01-23' AND cod_campaign = '$cc'";
		break;
	
		case 'brand':
		$sql= "SELECT c1.cod_campaign  FROM `campaigns` as c1
			WHERE c1.cod_brand IN (
				SELECT c2.cod_brand FROM campaigns as c2 WHERE c2.cod_campaign = '$cc'
			)";
		break;
		
		case 'partner':
		$sql= "SELECT c1.cod_campaign  FROM `campaigns` as c1
			WHERE c1.cod_partner IN (
				SELECT c2.cod_partner FROM campaigns as c2 WHERE c2.cod_campaign = '$cc'
			)";
		break;

		case 'db':
		$sql="SELECT * FROM clients WHERE email = '$q' AND updated_at > '2022-01-23'";
		break;
	}
}elseif($_GET['p'] != ''){
	switch($control){
		case 'campaign':
		$sql="SELECT * FROM clients WHERE phone = '$p' AND updated_at > '2022-01-23' AND cod_campaign = '$cc'";
		break;
	
		case 'brand':
		$sql= "SELECT c1.cod_campaign  FROM `campaigns` as c1
			WHERE c1.cod_brand IN (
				SELECT c2.cod_brand FROM campaigns as c2 WHERE c2.cod_campaign = '$cc'
			)";
		break;
		
		case 'partner':
		$sql= "SELECT c1.cod_campaign  FROM `campaigns` as c1
			WHERE c1.cod_partner IN (
				SELECT c2.cod_partner FROM campaigns as c2 WHERE c2.cod_campaign = '$cc'
			)";
		break;

		case 'db':
		$sql="SELECT * FROM clients WHERE phone = '$p' AND updated_at > '2022-01-23'";
		break;

	}
}
// li salvo in un risultato
$result = mysqli_query($con,$sql);

// Se esistono incremento il contatore hit_count, se no no
if(mysqli_num_rows($result)>0){
    $setCount = "UPDATE `clients` SET `hit_count` = `hit_count` + 1  WHERE (`email` = '$q' OR `phone` = '$p') AND `cod_campaign` = '$cc' ";
    mysqli_query($con, $setCount);

  $results = 'true';
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($results);
}else{
  header('Content-Type: application/json; charset=utf-8');
  $results = 'false';
  echo json_encode($results);
}

mysqli_close($con);
?>