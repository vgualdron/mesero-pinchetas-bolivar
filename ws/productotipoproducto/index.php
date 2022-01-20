<?php
session_start();
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
require_once("../conexion.php");
require_once("../encrypted.php");
$conexion = new Conexion();

$frm = json_decode(file_get_contents('php://input'), true);
// echo "**".$frm;
$idusuario = openCypher('decrypt', $frm['token']);
$urlaplicacion = $frm['url'];
// $idaplicacion = "2";

$conexion ->query("SET NAMES 'utf8';");

$use = $conexion->prepare("SELECT distinct
			tipr.*
			FROM pinchetas_restaurante.tipoproducto tipr
            WHERE tipr_estado = 'ACTIVO'
            ORDER BY tipr_orden;");
$use ->execute();
$count = $use->rowCount();
$row = $use->fetchAll();

$data = (object) array();
$array = array();

if ($count > 0) {
	foreach($row as $registro){

        $object = (object) array();
        $object->id =  $registro['tipr_id'];
        $object->descripcion = $registro['tipr_descripcion'];
        $object->orden = $registro['tipr_orden'];
        $children = array();

        $use2 = $conexion->prepare("SELECT distinct
                        prod.* 
                        FROM pinchetas_restaurante.producto prod
                        WHERE prod.tipr_id = ?
                        AND prod.prod_estado = 'ACTIVO'
                        AND prod.prod_cantidad > 0
                        ORDER BY prod.prod_descripcion;"); 

        $use2->bindValue(1, $registro['tipr_id']);

        $use2 ->execute();
        $count2 = $use2->rowCount();
        $row2 = $use2->fetchAll();

        if ($count2 > 0) {
            foreach($row2 as $registro2){
                $objChildren = (object) array();

                $objChildren->id =  $registro2['prod_id'];
                $objChildren->idtipoproducto =  $registro2['tipr_id'];
                $objChildren->descripcion =  $registro2['prod_descripcion'];
                $objChildren->orden =  $registro2['prod_orden'];
                $objChildren->costo =  $registro2['prod_costo'];
                $objChildren->precio =  $registro2['prod_precio'];
                $objChildren->cantidad =  $registro2['prod_cantidad'];

                array_push($children, $objChildren);
            }
        }

        $object->productos = $children;

        array_push($array, $object);
	}
}

$data->tiposProducto =  $array;
$data->estado = $count > 0 ? "OK" : "ERROR";
print_r(json_encode($data));

?>