<?php
session_start();
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

require_once("../conexion.php");
require_once("../encrypted.php");
require_once("./apiAlegra.php");
$conexion = new Conexion();
$apiAlegra = new ApiAlegra();

$frm = json_decode(file_get_contents('php://input'), true);

try {
  //Actualizar
  if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
      $input = $_GET;
      
      $id = $frm['id'];
      $idestado = $frm['idestado'];
      $registradopor = openCypher('decrypt', $frm['token']);
      $date = date("Y-m-d H:i:s");
      
      $nombreCliente = $frm['nombrecliente'];
      $direccionCliente = $frm['direccioncliente'];
      $telefonoCliente = $frm['telefonocliente'];
      $tipoPago = $frm['tipopago'];
      $facturar = $frm['facturar'];

      $prefijofactura = $frm['prefijofactura'];
      $numerofactura = $frm['numerofactura'];
      $idmesa = $frm['idmesa'];
      $clienteFE = $frm['clienteFE'];
      
      $date = date("Y-m-d");
      $bandera = true;

      $dataPayment = array(
        "idPedido" => $id,
        "idAccount" => 1, // caja general
        "date" => $date
      );

      $itemsInvoice = $apiAlegra->makeItemsInvoice($id);
      $paymentsInvoice = $apiAlegra->makePaymentsInvoice($dataPayment);
      $clientInvoice = $apiAlegra->makeClientInvoice($clienteFE);
      $warehouseInvoice = $apiAlegra->makeWarehouseInvoice($id);

      $dataInvoice = array(
        "date" => $date,
        "items" => $itemsInvoice,
        "client" => $clientInvoice,
        "warehouse" => $warehouseInvoice,
        "payments" => $paymentsInvoice,
        "paymentForm" => "CASH",
        "paymentMethod" => $tipoPago
      );

      $invoice = $apiAlegra->makeStampInvoice($dataInvoice);
      $result = $apiAlegra->stampInvoice($invoice);
      
      $cliente = $result["client"];

      $nombreCliente = $cliente["name"];
      $direccionCliente =  $cliente["address"]["address"];

      $numberTemplate = $result["numberTemplate"];

      $prefijofactura = $numberTemplate["prefix"];
      $numerofactura = $numberTemplate["number"];

      $sql = "UPDATE pinchetas_restaurante.pedido 
        SET espe_id = ?, pedi_registradopor = ?, pedi_fechacambio = ?,
        pedi_nombrecliente = ?,
        pedi_direccioncliente = ?,
        pedi_telefonocliente = ?,
        pedi_tipopago = ?,
        pedi_bandera = ?,
        pedi_numerofactura = ?,
        pedi_prefijofactura	= ?,
        mesa_id = ?
        WHERE pedi_id = ?; ";
          
      $sql = $conexion->prepare($sql);
      $sql->bindValue(1, $idestado);
      $sql->bindValue(2, $registradopor);
      $sql->bindValue(3, $date);
      $sql->bindValue(4, $nombreCliente);
      $sql->bindValue(5, $direccionCliente);
      $sql->bindValue(6, $telefonoCliente);
      $sql->bindValue(7, $tipoPago);
      $sql->bindValue(8, $bandera);
      $sql->bindValue(9, $numerofactura);
      $sql->bindValue(10, $prefijofactura);
      $sql->bindValue(11, $idmesa);
      $sql->bindValue(12, $id);

      $result = $sql->execute();
      
      if($result) {
        $input['id'] = $result;
        $input['mensaje'] = "Actualizado con éxito";
        header("HTTP/1.1 200 OK");
        echo json_encode($input);
        exit();
  	  } else {
        $input['id'] = $result;
        $input['mensaje'] = "Error actualizando";
        header("HTTP/1.1 400 Bad Request");
        echo json_encode($input);
        exit();
  	  }
  }

} catch (Exception $e) {
    echo 'Excepción capturada: ', $e->getMessage(), "\n";
}

//En caso de que ninguna de las opciones anteriores se haya ejecutado
// header("HTTP/1.1 400 Bad Request");

?>
