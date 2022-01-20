<?php
session_start();
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

require_once("../conexion.php");
require_once("../encrypted.php");
$conexion = new Conexion();

$frm = json_decode(file_get_contents('php://input'), true);

try {
  
  //  listar todos los posts o solo uno
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
      $registradopor = openCypher('decrypt', $_GET['token']);
      if (isset($_GET['telefono'])) {
         $sql = $conexion->prepare(" select 
                                    pedi.pedi_id as id,
                                    pedi.pedi_fecha as fecha,
                                    pedi.pege_idmesero as idmesero,
                                    pedi.mesa_id as idmesa,
                                    pedi.espe_id as idestado,
                                    espe.espe_descripcion as descripcionestado,
                                    espe.espe_editablepedido as editablepedido,
				    pedi.pedi_nombrecliente as nombrecliente,
				    pedi.pedi_direccioncliente as direccioncliente,
				    pedi.pedi_telefonocliente as telefonocliente,
				    pedi.pedi_tipopago as tipopago,
				    pedi.pedi_bandera as bandera
                                    FROM pinchetas_restaurante.pedido pedi
                                    inner join pinchetas_restaurante.estadopedido espe using (espe_id)
                                    WHERE pedi.pedi_telefonocliente = ? ;
				    order by pedi_id DESC limit 1");
                    							
        $sql->bindValue(1, $_GET['telefono']);
        $sql->execute();
        header("HTTP/1.1 200 OK");
        $result = $sql->fetch(PDO::FETCH_ASSOC);
        if ($result == false) {
          $data = (object) array();
          $data->mensaje = "No se encontró el registro.";
          header("HTTP/1.1 400 Bad Request");
          echo json_encode( $data );
          exit();
        } else {
          echo json_encode($result);
          exit();
        }
     }
  } 
  

} catch (Exception $e) {
    echo 'Excepción capturada: ', $e->getMessage(), "\n";
}

//En caso de que ninguna de las opciones anteriores se haya ejecutado
// header("HTTP/1.1 400 Bad Request");

?>
