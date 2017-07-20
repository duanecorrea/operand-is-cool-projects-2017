<?php

$db = new PDO('mysql:host=mariadb;dbname=operand_iscool','root','123456') or die('Erro');

$sql = "INSERT INTO agenda (ddd,numero,excluido) values";

for($i = 0; $i < 10000;$i++){
	$sql .="(:ddd$i, :numero$i, :excluido$i), ";
}

$sql = substr($sql, 0, -2).";";

try {

	$stmt = $db->prepare($sql);

	for($i = 0; $i < 10000; $i++){
		$ddd = rand(10,99);
		$numero = rand(1000,9999).rand(1000,9999);
		$excluido = rand(0,1);

		$stmt->bindvalue(":ddd$i",$ddd);
		$stmt->bindvalue(":numero$i",$numero);
		$stmt->bindvalue(":excluido$i",$excluido);
	}

	try{
		$stmt->execute();
	}catch(Exception $e){
		$stmt->debugDumpParams();
		echo "Código: 1 <br /> <pre>";
		print_r($e);
		exit();
	}

}catch(exception $e){
	echo "Codigo : 2 <br /> <pre>";
	print_r($e);
	exit();
}

echo "<br /> Script Finalizado! <br />";