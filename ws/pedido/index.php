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
      if (isset($_GET['id'])) {
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
				    pedi.pedi_bandera as bandera,
				    pedi.pedi_numerofactura as numerofactura,
            pedi.pedi_prefijofactura as prefijofactura,
            (select upper(rol.rol_descripcion) from pinchetas_general.rol rol
              inner join pinchetas_general.usuariorol usro on (rol.rol_id = usro.rol_id)
              inner join pinchetas_general.usuario usua on (usro.usua_id = usua.usua_id)
              inner join pinchetas_general.personageneral pege on (usua.pege_id = pege.pege_id)
              where pege.pege_id = ?) as descripcionRolSesion,
            (SELECT IF(COUNT(paan.paan_id) > 0, 'true', 'false') as isFE 
              FROM pinchetas_general.parametroano paan
              WHERE paan.paan_valor = pedi.pedi_prefijofactura
              AND paan.paan_descripcion = 'PREFIJO_CAJA_FE') as isFE
            FROM pinchetas_restaurante.pedido pedi
            inner join pinchetas_restaurante.estadopedido espe using (espe_id)
            WHERE pedi.pedi_id = ? ;");
                                  
        $sql->bindValue(1, $registradopor);                                    
        $sql->bindValue(2, $_GET['id']);
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
  	  } else {
        $date = date("Y-m-d");
        $sql = $conexion->prepare(" select 
          pedi.pedi_id as id,
          pedi.pedi_fecha as fecha,
          pedi.pege_idmesero as idmesero,
          pedi.mesa_id as idmesa,
          pedi.espe_id idestado,
          pedi.pedi_nombrecliente as nombrecliente,
          pedi.pedi_direccioncliente as direccioncliente,
          pedi.pedi_telefonocliente as telefonocliente,
          pedi.pedi_tipopago as tipopago,
          pedi.pedi_bandera as bandera,
          pedi.pedi_numerofactura as numerofactura,
          pedi.pedi_prefijofactura as prefijofactura
          FROM pinchetas_restaurante.pedido pedi
          order by pedi.pedi_fechacambio; ");

        $sql->bindValue(1, $registradopor);
        $sql->bindValue(2, $date);
        $sql->execute();
        $sql->setFetchMode(PDO::FETCH_ASSOC);
        header("HTTP/1.1 200 OK");
        echo json_encode( $sql->fetchAll() );
        exit();
  	  }
  }
  // Crear un nuevo post
  else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $input = $_POST;
          
      $idmesa = $frm['idmesa'];
      $idestado = '1';
      $registradopor = openCypher('decrypt', $frm['token']);
      $date = date("Y-m-d H:i:s");
      $fecha = date("Y-m-d");
      $tipopago = 'EFECTIVO';
      
      $sql = "INSERT INTO 
              pinchetas_restaurante.pedido(pedi_fecha, pege_idmesero, mesa_id, espe_id, pedi_registradopor, pedi_fechacambio, pedi_tipopago)
              VALUES (?, ?, ?, ?, ?, ?, ?); ";
            
      $sql = $conexion->prepare($sql);
      $sql->bindValue(1, $date);
      $sql->bindValue(2, $registradopor);
      $sql->bindValue(3, $idmesa);
      $sql->bindValue(4, $idestado);
      $sql->bindValue(5, $registradopor);
      $sql->bindValue(6, $date);
      $sql->bindValue(7, $tipopago);
      $sql->execute();
      $postId = $conexion->lastInsertId();
 

    $input['id'] = $postId;
    $input['mensaje'] = "Registrado con éxito";
    header("HTTP/1.1 200 OK");
    echo json_encode($input);
    exit();
  	  
  }
  //Actualizar
  else if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
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
      
      $date = date("Y-m-d");

      $bandera = false;
      
      if ($numerofactura > 0) {
      	$bandera = true;
      } else if ($tipoPago == "TARJETA") {
	      $bandera = true;
      } else if (!empty($nombreCliente) && !empty($direccionCliente) && !empty($telefonoCliente)) {
	      $bandera = true;
      } else if ($facturar == "SI") {
        $bandera = true;
      } else {
        $bandera = false;
      }

      if ($numerofactura < 1 && $bandera == true) {
	      $sql = $conexion->prepare(" select 
          ((COALESCE(max(pedi_numerofactura), 0)) + 1) as numerofactura,
          (select paan_valor from pinchetas_general.parametroano paan where paan_descripcion = ?) as prefijofactura
          from pinchetas_restaurante.pedido
          where pedi_prefijofactura = (select paan_valor from pinchetas_general.parametroano paan where paan_descripcion = ?);");
                    							
        $sql->bindValue(1, 'PREFIJO_CAJA');
        $sql->bindValue(2, 'PREFIJO_CAJA');
                                
        $sql->execute();
        $result = $sql->fetch(PDO::FETCH_ASSOC);
 
        $numerofactura = $result['numerofactura'];
        $prefijofactura = $result['prefijofactura'];
      }
	
      if ($idestado == '7') {
          
        $sql = "CALL enviar_a_facturado_el_pedido(?,?,?,?); ";
          
        $sql = $conexion->prepare($sql);
        $sql->bindValue(1, $id);
        $sql->bindValue(2, $idestado);
        $sql->bindValue(3, $registradopor);
        $sql->bindValue(4, $date);

	      $result = $sql->execute();

        $sql = "UPDATE pinchetas_restaurante.pedido 
          SET 
          espe_id = ?, 
          pedi_registradopor = ?, 
          pedi_fechacambio = ?,
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
      } else {
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
      }
      
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
  // Eliminar
  else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
      $input = $_GET;
      $id = $input['id'];
      $registradopor = openCypher('decrypt', $input['token']);

      $date = date("Y-m-d H:i:s");
      
      $sql = "CALL procedimiento_eliminar_pedido(?, ?); ";
            
      $sql = $conexion->prepare($sql);
      $sql->bindValue(1, $id);
      $sql->bindValue(2, $registradopor);
      $result = $sql->execute();
      if($result) {
        $output['id'] = $postId;
        $output['mensaje'] = "Eliminado con éxito";
        header("HTTP/1.1 200 OK");
        echo json_encode($output);
        exit();
  	  } else {
        $output['id'] = $postId;
        $output['mensaje'] = "Error eliminando";
        header("HTTP/1.1 400 Bad Request");
        echo json_encode($output);
        exit();
  	  }
  }

} catch (Exception $e) {
    echo 'Excepción capturada: ', $e->getMessage(), "\n";
}

//En caso de que ninguna de las opciones anteriores se haya ejecutado
// header("HTTP/1.1 400 Bad Request");

?>
