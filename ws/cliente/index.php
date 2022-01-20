<?php
session_start();
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

require_once("../conexion.php");
require_once("../encrypted.php");
require_once("../pedido/apiAlegra.php");
$conexion = new Conexion();
$apiAlegra = new ApiAlegra();

$frm = json_decode(file_get_contents('php://input'), true);

try {
  
  //  listar todos los posts o solo uno
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $identificationNumber = $_GET['identification'];
    $response = $apiAlegra->getClient($identificationNumber);
    if(empty($response)) {
		$clientInvoice = (object) $response;
	} else {
		$clientInvoice = $response[0];
		if ($clientInvoice["nameObject"]) {
			$clientInvoice["name"] = $clientInvoice["nameObject"]; 
		}
    }
    header("HTTP/1.1 200 OK");
    echo json_encode( $clientInvoice );
    exit();
  	  
  }

} catch (Exception $e) {
    echo 'Excepción capturada: ', $e->getMessage(), "\n";
}

//En caso de que ninguna de las opciones anteriores se haya ejecutado
// header("HTTP/1.1 400 Bad Request");

?>
