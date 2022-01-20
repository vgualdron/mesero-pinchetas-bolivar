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
      if (isset($_GET['id'])) {
        $sql = $conexion->prepare("SELECT distinct
                                    mesa.mesa_id as id,
                                    mesa.mesa_descripcion as descripcion,
                                    mesa.mesa_orden as orden,
                                    mesa.mesa_estado as estado
                                    FROM pinchetas_restaurante.mesa mesa
                                    where mesa.mesa_id = ?
                                    order by mesa.mesa_orden; ");
                    							
        $sql->bindValue(1, $_GET['id']);                                
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
        $sql = $conexion->prepare(" SELECT distinct
                                    mesa.mesa_id as id,
                                    mesa.mesa_descripcion as descripcion,
                                    mesa.mesa_orden as orden,
                                    mesa.mesa_estado as estado,
                                    pedi.pedi_id as usada,
                                    pedi.pedi_fecha as fechapedido,
                                    pedi.pege_idmesero as idmesero,
                                    CONCAT(pena.pena_primernombre, ' ', pena.pena_primerapellido) as mesero,
                                    TIMESTAMPDIFF(MINUTE, pedi.pedi_fecha, NOW()) as minutos,
                                    espe.espe_id as idestadopedido,
                                    espe.espe_descripcion as descripcionestadopedido
                                    FROM pinchetas_restaurante.mesa mesa
                                    LEFT JOIN pinchetas_restaurante.pedido pedi on (pedi.mesa_id = mesa.mesa_id and pedi.espe_id in (1,2,3,4,5))
                                    LEFT JOIN pinchetas_restaurante.estadopedido espe on (espe.espe_id = pedi.espe_id)
                                    LEFT JOIN pinchetas_general.personanatural pena on (pedi.pege_idmesero = pena.pege_id)
                                    WHERE mesa.mesa_estado = 'ACTIVO'
                                    order by mesa.mesa_orden; ");
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
      $orden = $frm['orden'];
      $estado = $frm['estado'];
      $registradopor = openCypher('decrypt', $frm['token']);
      $date = date("Y-m-d H:i:s");
      
      $sql = "INSERT INTO 
              pinchetas_restaurante.mesa (mesa_descripcion, mesa_orden, mesa_estado, mesa_registradopor, mesa_fechacambio)
              VALUES (?, ?, ?, ?, ?); ";
            
      $sql = $conexion->prepare($sql);
      $sql->bindValue(1, $descripcion);
      $sql->bindValue(2, $orden);
      $sql->bindValue(3, $estado);
      $sql->bindValue(4, $registradopor);
      $sql->bindValue(5, $date);
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
      $orden = $frm['orden'];
      $estado = $frm['estado'];
      $registradopor = openCypher('decrypt', $frm['token']);
      $date = date("Y-m-d H:i:s");
      
      $sql = "UPDATE pinchetas_restaurante.mesa 
              SET mesa_descripcion = ?, mesa_orden = ?, mesa_estado = ?, mesa_registradopor = ?, mesa_fechacambio = ?
              WHERE mesa_id = ?; ";
            
      $sql = $conexion->prepare($sql);
      $sql->bindValue(1, $descripcion);
      $sql->bindValue(2, $orden);
      $sql->bindValue(3, $estado);
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
      
      $sql = "CALL procedimiento_eliminar_mesa(?, ?); ";
            
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
