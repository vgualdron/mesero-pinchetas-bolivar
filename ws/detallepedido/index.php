<?php
session_start();
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

require_once("../conexion.php");
require_once("../encrypted.php");
$conexion = new Conexion();

require_once("../ticket/detallepedido.php");

$frm = json_decode(file_get_contents('php://input'), true);

try {
  
  //  listar todos los posts o solo uno
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
      $registradopor = openCypher('decrypt', $_GET['token']);
      if (isset($_GET['id'])) {
        $sql = $conexion->prepare(" select 
                                    depe.depe_id as id,
                                    depe.depe_descripcion as descripcion,
                                    depe.pedi_id as idpedido,
                                    tipr.tipr_id as idtipoproducto,
                                    tipr.tipr_descripcion as descripciontipoproducto,
                                    depe.prod_id as idproducto,
                                    prod.prod_descripcion as descripcionproducto,
                                    depe.prod_costo as costoproducto,
                                    depe.prod_cantidad as cantidadproducto,
                                    depe.prod_precio as precioproducto
                                    FROM pinchetas_restaurante.detallepedido depe
                                    inner join pinchetas_restaurante.pedido pedi on (pedi.pedi_id = depe.pedi_id)
                                    inner join pinchetas_restaurante.producto prod on (prod.prod_id = depe.prod_id)
                                    inner join pinchetas_restaurante.tipoproducto tipr on (tipr.tipr_id = prod.tipr_id)
                                    WHERE depe.depe_id = ? 
                                    ORDER BY depe_fechacambio;");
                    							
        $sql->bindValue(1, $_GET['id']);
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
  	  } else if (isset($_GET['idpedido'])) {
        $date = date("Y-m-d");
        $sql = $conexion->prepare(" select 
                                    depe.depe_id as id,
                                    depe.depe_descripcion as descripcion,
                                    depe.pedi_id as idpedido,
                                    tipr.tipr_id as idtipoproducto,
                                    tipr.tipr_descripcion as descripciontipoproducto,
                                    depe.prod_id as idproducto,
                                    prod.prod_descripcion as descripcionproducto,
                                    depe.prod_costo as costoproducto,
                                    depe.prod_cantidad as cantidadproducto,
                                    depe.prod_precio as precioproducto
                                    FROM pinchetas_restaurante.detallepedido depe
                                    inner join pinchetas_restaurante.pedido pedi on (pedi.pedi_id = depe.pedi_id)
                                    inner join pinchetas_restaurante.producto prod on (prod.prod_id = depe.prod_id)
                                    inner join pinchetas_restaurante.tipoproducto tipr on (tipr.tipr_id = prod.tipr_id)
                                    WHERE depe.pedi_id = ?
                                    ORDER BY depe_fechacambio;");
        $sql->bindValue(1, $_GET['idpedido']);
        $sql->execute();
        $sql->setFetchMode(PDO::FETCH_ASSOC);
        header("HTTP/1.1 200 OK");
        echo json_encode( $sql->fetchAll() );
        exit();
  	  } else {
        $date = date("Y-m-d");
        $sql = $conexion->prepare(" depe.depe_id as id,
                                    depe.depe_descripcion as descripcion,
                                    depe.pedi_id as idpedido,
                                    tipr.tipr_id as idtipoproducto,
                                    tipr.tipr_descripcion as descripciontipoproducto,
                                    depe.prod_id as idproducto,
                                    prod.prod_descripcion as descripcionproducto,
                                    depe.prod_costo as costoproducto,
                                    depe.prod_cantidad as cantidadproducto,
                                    depe.prod_precio as precioproducto
                                    FROM pinchetas_restaurante.detallepedido depe
                                    inner join pinchetas_restaurante.pedido pedi on (pedi.pedi_id = depe.pedi_id)
                                    inner join pinchetas_restaurante.producto prod on (prod.prod_id = depe.prod_id)
                                    inner join pinchetas_restaurante.tipoproducto tipr on (tipr.tipr_id = prod.tipr_id)
                                    ORDER BY depe_fechacambio;");
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

      $idestadopedido = 0;
      $descripcion = $frm['descripcion'];
      $pedido = $frm['pedido'];
      $idpedido = $frm['idpedido'];
      $idproducto = $frm['idproducto'];
      $cantidadproducto = $frm['cantidadproducto'];
      $registradopor = openCypher('decrypt', $frm['token']);
      $date = date("Y-m-d H:i:s");

      if ($pedido) {
        $idestadopedido = $pedido['idestado'];
      }
      if ($idestadopedido > 1) {
        printCommand($frm, 'ADD');
      }
      
      $sql = "INSERT INTO 
              pinchetas_restaurante.detallepedido(depe_descripcion, pedi_id, prod_id, prod_costo, prod_cantidad, prod_precio, depe_registradopor, depe_fechacambio)
              VALUES (
              ?,
              ?,
              ?,
              (select prod_costo from pinchetas_restaurante.producto where prod_id = ?),
              ?,
              (select prod_precio from pinchetas_restaurante.producto where prod_id = ?),
              ?,
              ?); ";
            
      $sql = $conexion->prepare($sql);
      $sql->bindValue(1, $descripcion);
      $sql->bindValue(2, $idpedido);
      $sql->bindValue(3, $idproducto);
      $sql->bindValue(4, $idproducto);
      $sql->bindValue(5, $cantidadproducto);
      $sql->bindValue(6, $idproducto);
      $sql->bindValue(7, $registradopor);
      $sql->bindValue(8, $date);
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
      $descripcion = $frm['descripcion'];
      $idpedido = $frm['idpedido'];
      $idproducto = $frm['idproducto'];
      $cantidadproducto = $frm['cantidadproducto'];
      $registradopor = openCypher('decrypt', $frm['token']);
      $date = date("Y-m-d H:i:s");

      $idestadopedido = 0;
      $pedido = $frm['pedido'];

      if ($pedido) {
        $idestadopedido = $pedido['idestado'];
      }
      if ($idestadopedido > 1) {
        printCommand($frm, 'EDIT');
      }
      
      $sql = "UPDATE pinchetas_restaurante.detallepedido 
              SET depe_descripcion = ?,
              pedi_id = ?,
              prod_id = ?,
              prod_costo = (select prod_costo from pinchetas_restaurante.producto where prod_id = ?),
              prod_cantidad = ?,
              prod_precio = (select prod_precio from pinchetas_restaurante.producto where prod_id = ?),
              depe_registradopor = ?,
              depe_fechacambio = ?
              WHERE depe_id = ?; ";
            
      $sql = $conexion->prepare($sql);
      $sql->bindValue(1, $descripcion);
      $sql->bindValue(2, $idpedido);
      $sql->bindValue(3, $idproducto);
      $sql->bindValue(4, $idproducto);
      $sql->bindValue(5, $cantidadproducto);
      $sql->bindValue(6, $idproducto);
      $sql->bindValue(7, $registradopor);
      $sql->bindValue(8, $date);
      $sql->bindValue(9, $id);
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
  // Eliminar
  else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
      $input = $_GET;
      $id = $input['id'];
      $registradopor = openCypher('decrypt', $input['token']);

      $idestadopedido = 0;
      $pedido = $frm['pedido'];

      if ($pedido) {
        $idestadopedido = $pedido['idestado'];
      }
      if ($idestadopedido > 1) {
        printCommand($frm, 'CANCEL');
      }

      $date = date("Y-m-d H:i:s");
      
      $sql = "CALL procedimiento_eliminar_detallepedido(?, ?); ";
            
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