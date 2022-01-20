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
        $sql = $conexion->prepare("SELECT distinct
                                    gast.gast_id as id,
                                    gast.gast_descripcion as descripcion,
                                    gast.gast_valor as valor,
                                    gast.gast_fecha as fecha,
                                    CONCAT(pena.pena_primernombre, pena.pena_primerapellido) as nombrepersona
                                    FROM pinchetas_restaurante.gasto gast
                                    inner join pinchetas_general.personageneral pege on (pege.pege_id = gast.pege_idregistrador)
                                    inner join pinchetas_general.personanatural pena on (pena.pege_id = pege.pege_id)
                                    where gast.gast_id = ?
                                    and gast.pege_idregistrador = ?
                                    order by gast.gast_fecha; ");
                    							
        $sql->bindValue(1, $_GET['id']);
        $sql->bindValue(2, $registradopor);
        $sql->execute();
        header("HTTP/1.1 200 OK");
        $result = $sql->fetch(PDO::FETCH_ASSOC);
        if ($result == false) {
          $data = (object) array();
          $data->mensaje = "No se encontraron registros.";
          header("HTTP/1.1 400 Bad Request");
          echo json_encode( $data );
          exit();
        } else {
          echo json_encode($result);
          exit();
        }
  	  } else {
        $date = date("Y-m-d");
        $sql = $conexion->prepare("SELECT distinct
                                    gast.gast_id as id,
                                    gast.gast_descripcion as descripcion,
                                    gast.gast_valor as valor,
                                    gast.gast_fecha as fecha,
                                    CONCAT(pena.pena_primernombre, ' ', pena.pena_primerapellido) as nombrepersona
                                    FROM pinchetas_restaurante.gasto gast
                                    inner join pinchetas_general.personageneral pege on (pege.pege_id = gast.pege_idregistrador)
                                    inner join pinchetas_general.personanatural pena on (pena.pege_id = pege.pege_id)
                                    where gast.pege_idregistrador = ?
                                    and gast.gast_fecha = ?
                                    order by gast.gast_fecha; ");
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
          
      $descripcion = $frm['descripcion'];
      $valor = $frm['valor'];
      $fecha = $frm['fecha'];
      $registradopor = openCypher('decrypt', $frm['token']);
      $date = date("Y-m-d H:i:s");
      
      $sql = "INSERT INTO 
              pinchetas_restaurante.gasto (gast_descripcion, gast_valor, gast_fecha, pege_idregistrador, gast_registradopor, gast_fechacambio)
              VALUES (?, ?, ?, ?, ?, ?); ";
            
      $sql = $conexion->prepare($sql);
      $sql->bindValue(1, $descripcion);
      $sql->bindValue(2, $valor);
      $sql->bindValue(3, $fecha);
      $sql->bindValue(4, $registradopor);
      $sql->bindValue(5, $registradopor);
      $sql->bindValue(6, $date);
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
      $valor = $frm['valor'];
      $fecha = $frm['fecha'];
      $registradopor = openCypher('decrypt', $frm['token']);
      $date = date("Y-m-d H:i:s");
      
      $sql = "UPDATE pinchetas_restaurante.gasto 
              SET gast_descripcion = ?, gast_valor = ?, gast_fecha = ?, gast_registradopor = ?, gast_fechacambio = ?
              WHERE gast_id = ?; ";
            
      $sql = $conexion->prepare($sql);
      $sql->bindValue(1, $descripcion);
      $sql->bindValue(2, $valor);
      $sql->bindValue(3, $fecha);
      $sql->bindValue(4, $registradopor);
      $sql->bindValue(5, $date);
      $sql->bindValue(6, $id);
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

      $date = date("Y-m-d H:i:s");
      
      $sql = "CALL procedimiento_eliminar_gasto(?, ?); ";
            
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