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
    
    $sql = $conexion->prepare(" SELECT
      CONCAT(muni.muni_descripcion, ' - ', depa.depa_descripcion) as text,
      muni.muni_descripcion as value,
      muni.muni_descripcion as city,
      depa.depa_descripcion as department,
      pais.pais_descripcion as country
      FROM pinchetas_general.municipio muni
      INNER JOIN pinchetas_general.departamento depa ON (depa.depa_id = muni.depa_id)
      INNER JOIN pinchetas_general.pais pais ON (pais.pais_id = depa.pais_id); ");

    $sql->execute();
    $sql->setFetchMode(PDO::FETCH_ASSOC);
    header("HTTP/1.1 200 OK");
    echo json_encode( $sql->fetchAll() );
    exit();
  	  
  }

} catch (Exception $e) {
    echo 'Excepción capturada: ', $e->getMessage(), "\n";
}

//En caso de que ninguna de las opciones anteriores se haya ejecutado
// header("HTTP/1.1 400 Bad Request");

?>
