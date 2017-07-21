<?php

header('Content-Type: text/html; charset=utf-8');
header("Access-Control-Allow-Methods:GET,POST,PUT,DELETE,OPTIONS");
header("Access-Control-Allow-Origin:*");

use Phalcon\DI\FactoryDefault,
	Phalcon\db\Adapter\Pdo\Mysql,
	Phalcon\Mvc\Micro,
	Phalcon\Http\Response;

$di = new FactoryDefault();

$di->set('db', function(){
	return new Mysql(array(
		"host" => "mariadb",
		"username" => "root",
		"password" => "123456",
		"dbname" => "operand_iscool"
	));
});

$app = new Micro($di);

$app->get('/', function() use ($app){
	//echo 'Página Inicial!';	
	include("./index.html");
});


$app->get('/v1/bankaccounts', function() use ($app){
	$sql = "SELECT id,name,balance FROM bank_account ORDER BY name";
	$result = $app->db->query($sql);
	$result->setFetchMode(Phalcon\Db::FETCH_OBJ);
	
	$data = array();

	while($bankAccount = $result->fetch()){
		$data[] = array(
			'id' => $bankAccount->id,
			'name' => $bankAccount ->name,
			'balance' => $bankAccount->balance,
		);
	}

	$response = new Response();

	if ($data == false){
		$response->setStatusCode(404,"Not Found");
		$response->setJsonContent(array('status' => 'NOT-FOUND'));
	} else {
		$response->setJsonContent(Array(
			'status' => 'FOUND',
			'data' => $data
		));
	}

	return $response;

});

$app->post('/v1/bankaccounts', function() use ($app){

	$bankAccount = $app->request->getpost();
	$response = new Phalcon\Http\Response();

	if(!$bankaccount){
		$bankaccount = (array) $app->request->getJsonRawBody();
	}

	try{
		$result = $app->db->insert("bank_account",
			array($bankAccount['name'],$bankAccount['balance']),
			array("name","balance")
		);

		$response->setStatusCode(201,"Created");
		$bankAccount['id'] = $app->db->lastInsertId();
		$response->setJsonContent(array('status' => 'OK', 'data' => $bankAccount));

	} catch (Exception $e){

		$response->setStatusCode(409,"Conflict");
		$errors[] = $e->getMessage();
		$response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));

	}

	return $response;

});	


$app->put('/v1/bankaccounts/{id:[0-9]+}', function($id) use($app) {
	$bankAccount = $app->request->getPut();
	$response = new Phalcon\Http\Response();

	try{
		$result = $app->db->update("bank_account",
			array("name", "balance"),
			array($bankAccount['name'], $bankAccount['balance']),
			"id = $id"
		);

		$response->setJsonContent(array('stauts' => 'OK'));

	} catch (Exception $e) {
		$response->setStatusCode(409, "Conflict");
		$errors[] = $e->getMessage();
		$response->SetJsonContent(array('status' => 'ERROR', 'messages' => $errors));
	}

	return $response;
});

$app->delete('/v1/bankaccounts/{id:[0-9]+}', function($id) use($app){
	$response = new Phalcon\Http\Response();

	try{

		$result = $app->db->delete("bank_account",
			"id = $id"
		);

		$response->setJsonContent(array('status' => 'OK'));

	}catch(Exception $e){
		$response->setStatusCode(409,"conflict");
		$errors[] = $e->getMessage();
		$response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));
	};

	return $response;
});

$app->get('/v1/bankaccounts/search/{id:[0-9]+}', function($id) use ($app){
	$sql = "SELECT id,name,balance FROM bank_account WHERE id = ?";
	$result = $app->db->query($sql, array($id));
	$result->setFetchMode(Phalcon\Db::FETCH_OBJ);
	
	$data = array();
	$bankAccount = $result->fetch();
	$response = new Response();

	if ($bankAccount == false){
		$response->setStatusCode(404,"Not Found");
		$response->setJsonContent(array('status' => 'NOT-FOUND'));
	} else {
		$sqlOperations = "SELECT id, operation, bank_account_id, date, value FROM bank_account_operations WHERE bank_account_id = " . $id . " ORDER BY date";
		$resultOperations = $app->db->query($sqlOperations);
		$resultOperations->setFetchMode(Phalcon\Db::FETCH_OBJ);		
		$bankAccountOperations = $resultOperations->fetchAll();

		$response->setJsonContent(array(
			'id' => $bankAccount->id,
			'name' => $bankAccount->name,
			'balance' => $bankAccount->balance,
			'operations' => $bankAccountOperations
		));
		return $response;
	}

});

$app->post('/v1/bankaccounts/deposit', function() use ($app){
	$depositInfo = $app->request->getpost();
	$response = new Phalcon\Http\Response();

	if(!$depositInfo){
		$depositInfo = (array) $app->request->getJsonRawBody();
	}

	try{
		$result = $app->db->insert("bank_account_operations",
			array('deposit',$depositInfo['bank_account_id'], $depositInfo['value'],date('Y-m-d H:i:s')),
			array('operation','bank_account_id','value','date')
		);

		$sqlUpdate = "UPDATE bank_account set balance = (
			SELECT SUM(value) as balance from bank_account_operations WHERE
				bank_account_id = ?
			) where id = ?";
		$app->db->query($sqlUpdate, array($depositInfo['bank_account_id'],
			$depositInfo['bank_account_id']));

		$response->setStatusCode(201,"created");
		$response->setJsonContent(array('status' =>'OK'));

	} catch (Exception $e){

		$response->setStatusCode(409,"Conflict");
		$errors[] = $e->getMessage();
		$response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));

	}

	return $response;


});	

$app->post('/v1/bankaccounts/withdrawal', function() use ($app){
	$withdrawalInfo = $app->request->getpost();
	$response = new Phalcon\Http\Response();

	if(!$withdrawalInfo){
		$withdrawalInfo = (array) $app->request->getJsonRawBody();
	}

	try{
		$result = $app->db->insert("bank_account_operations",
			array('withdrawal',$withdrawalInfo['bank_account_id'], $withdrawalInfo['value'] * -1,date('Y-m-d H:i:s')),
			array('operation','bank_account_id','value','date')
		);

		$sqlUpdate = "UPDATE bank_account set balance = (
			SELECT SUM(value) as balance from bank_account_operations WHERE
				bank_account_id = ?
			) where id = ?";
		$app->db->query($sqlUpdate, array($withdrawalInfo['bank_account_id'],
			$withdrawalInfo['bank_account_id']));

		$response->setStatusCode(201,"created");
		$response->setJsonContent(array('status' =>'OK'));

	} catch (Exception $e){

		$response->setStatusCode(409,"Conflict");
		$errors[] = $e->getMessage();
		$response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));

	}

	return $response;


});	


$app->notFound(function () use ($app) {
	$app->response->setStatusCode(404,"Not Found")->sendHeaders();
	echo 'This is crazy, but this page was not found!';
});


$app->handle();

/*$app = new Phalcon\Mvc\Micro();

$app-> get('/diga/ola/{nome}', function($nome) {
	echo json_encode(array($nome, "uma", "informacao", "importante"));	
});

$app->notFound(function () use ($app) {
	$app->response->setStatusCode(404,"Not Found")->sendHeaders();
	echo 'This is crazy, but this page was not found!';
});

$app->handle();*/

/*class Usuario {

	protected $id;
	protected $nome;
	protected $email;

	public function __construct($id) {
		$this->id = $id;
	}	 

	public function setId($id){
		$this->id = $id;
	}
	public function getId(){
		return $this->id;
	}

	public function setNome($nome){
		$this->nome = $nome;
	}
	public function getNome(){
		return $this->nome;
	}

	public function setEmail($email){
		$this->email = $email;
	}
	public function getEmail(){
		return $this->email;
	}

}

class Admin extends Usuario{

	protected $password;

	public function setPassword($password){
		$this->password = md5($password);
	}
	public function getPassword(){
		return $this->password;
	}

}

$user = new Admin(1);

$user->setNome('dvc');
$user->setEmail('dvc@totvs.com.br');
$user->setPassword('12345');

echo 'ID: ' . $user->getId() . '<br>' . 
	 'Nome: ' . $user->getNome() . '<br>' . 
	 'E-mail: ' . $user->getEmail() . '<br>' . 
	 'Senha: ' . $user->getPassword();
	 */