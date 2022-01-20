<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
require __DIR__ . '/ticket/autoload.php'; 
//Nota: si renombraste la carpeta a algo diferente de "ticket" cambia el nombre en esta línea
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

date_default_timezone_set('America/Bogota');

$unwanted_array = array('Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A',                                     'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
 
/*
	Este ejemplo imprime un
	ticket de venta desde una impresora térmica
*/
 
 
/*
	Una pequeña clase para
	trabajar mejor con
	los productos
	Nota: esta clase no es requerida, puedes
	imprimir usando puro texto de la forma
	que tú quieras
*/
require_once("../conexion.php");
require_once("../encrypted.php");
$conexion = new Conexion();
$conexion ->query("SET NAMES 'utf8';");

$frm = json_decode(file_get_contents('php://input'), true);

$idpena = openCypher('decrypt', $frm['token']);
$tipoPago = $frm['tipopago'];

$use = $conexion->prepare(" select * from pinchetas_general.parametroano paan
			    where paan.paan_descripcion = ? ; "); 						
$use->bindValue(1, 'NOMBRE_EMPRESA');
$use ->execute();
$row = $use->fetch();
$nombreEmpresa = $row['paan_valor'];


$use = $conexion->prepare(" select * from pinchetas_general.parametroano paan
			    where paan.paan_descripcion = ? ; "); 						
$use->bindValue(1, 'NIT_EMPRESA');
$use ->execute();
$row = $use->fetch();
$nitEmpresa = $row['paan_valor'];


$use = $conexion->prepare(" select * from pinchetas_general.parametroano paan
			    where paan.paan_descripcion = ? ; "); 						
$use->bindValue(1, 'DIRECCION_EMPRESA');
$use ->execute();
$row = $use->fetch();
$direccionEmpresa = $row['paan_valor'];

$use = $conexion->prepare(" select * from pinchetas_general.parametroano paan
			    where paan.paan_descripcion = ? ; "); 						
$use->bindValue(1, 'TELEFONO_EMPRESA');
$use ->execute();
$row = $use->fetch();
$telefonoEmpresa = $row['paan_valor'];

$use = $conexion->prepare(" select * from pinchetas_general.parametroano paan
			    where paan.paan_descripcion = ? ; "); 						
$use->bindValue(1, 'RESOLUCION_FACTURA');
$use ->execute();
$row = $use->fetch();
$resolucionEmpresa = $row['paan_valor'];

$use = $conexion->prepare(" select * from pinchetas_general.parametroano paan
			    where paan.paan_descripcion = ? ; "); 						
$use->bindValue(1, 'RANGO_FACTURACION');
$use ->execute();
$row = $use->fetch();
$rangoAutorizadoFacturas = $row['paan_valor'];

$use = $conexion->prepare(" select * from pinchetas_general.parametroano paan
			    where paan.paan_descripcion = ? ; "); 						
$use->bindValue(1, 'PREFIJO_CAJA');
$use ->execute();
$row = $use->fetch();
$prefijoFactura = $row['paan_valor'];

$use = $conexion->prepare(" SELECT * FROM pinchetas_general.personanatural pena
							WHERE pena.pege_id = ?; "); 						
$use->bindValue(1, $idpena);
$use ->execute();
$row = $use->fetch();
$nombreGenerador = $row['pena_primernombre']. " " . $row['pena_primerapellido'];

$fecha = date("Y-m-d");
$hora = date("H:i:s");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $productos = $frm['items'];
	$gastos = $frm['gastos'];
    $fecha = $frm['fecha'];
	$titulo = $frm['titulo'];
    /*
        Aquí, en lugar de "POS-58" (que es el nombre de mi impresora)
        escribe el nombre de la tuya. Recuerda que debes compartirla
        desde el panel de control
    */

    $nombre_impresora = "POS-80"; 


    $connector = new WindowsPrintConnector($nombre_impresora);
    $printer = new Printer($connector);


    /*
        Vamos a imprimir un logotipo
        opcional. Recuerda que esto
        no funcionará en todas las
        impresoras

        Pequeña nota: Es recomendable que la imagen no sea
        transparente (aunque sea png hay que quitar el canal alfa)
        y que tenga una resolución baja. En mi caso
        la imagen que uso es de 250 x 250
    */

    # Vamos a alinear al centro lo próximo que imprimamos
    $printer->setJustification(Printer::JUSTIFY_CENTER);

    /*
        Intentaremos cargar e imprimir
        el logo
    */
    try{
        $logo = EscposImage::load("logo_banner_menu_minimized.jpg", false);
        $printer->bitImage($logo);
    }catch(Exception $e){/*No hacemos nada si hay error*/}

    /*
        Ahora vamos a imprimir un encabezado
    */
    $printer->feed(1);
    $printer->setEmphasis(true);
    $printer->setTextSize(2,1);
    $printer->text("CIERRE DE CAJA DEL DIA \n" . $fecha . "\n");
    $printer->setTextSize(1,1);
	
	$printer->feed(1);
	$printer->setTextSize(1,1);
	
	$printer->setJustification(Printer::JUSTIFY_LEFT);
	
    $printer->text("FECHA GENERACION    : " . $fecha. " HORA " . $hora . "\n");
	// $printer->text("GENERADO POR        : " . $nombreGenerador . "\n");

    $printer->selectPrintMode();
    
	
	
	$printer->selectPrintMode();
	$printer->text("------------------------------------------------\n");
	$printer->feed(1);
	$printer->setJustification(Printer::JUSTIFY_CENTER);
	$printer->text("INGRESOS\n");
	$printer->feed(1);
    $printer->setEmphasis(true);
    $printer->text("CANT                DESCRIPCION           TOTAL\n");
    $printer->selectPrintMode();
    $printer->text("------------------------------------------------\n");
    
   
    $total = 0;
	$totalVentas = 0;
    foreach ($productos as $clave => $producto) {
        $total = $producto["cantidad"] * $producto["prod_precio"];
		$totalVentas += $total;
        /*Alinear a la izquierda para la cantidad y el nombre*/
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("  ".substr($producto["cantidad"],0,22). "   ".strtr( $producto["prod_descripcion"], $unwanted_array ). "\n");

        /*Y a la derecha para el importe*/
        $printer->setJustification(Printer::JUSTIFY_RIGHT);$printer->text(' $' . number_format($total, 0, ',', '.') ."\n");
		$printer->text("- - - - - - - - - - - - - - - - - - - - - - - -\n");
	}
	
	$printer->text("TOTAL INGRESOS :" . ' $' . number_format($totalVentas, 0, ',', '.') . "\n");
	
	$printer->selectPrintMode();
	$printer->text("------------------------------------------------\n");
	$printer->feed(1);
	$printer->setJustification(Printer::JUSTIFY_CENTER);
	$printer->text("EGRESOS\n");
	$printer->feed(1);
    $printer->setEmphasis(true);
    $printer->text("DESCRIPCION                           VALOR\n");
    $printer->selectPrintMode();
    $printer->text("------------------------------------------------\n");
    
   
    $total = 0;
	$totalGastos = 0;
    foreach ($gastos as $clave => $gasto) {
        $total = $gasto["valor"];
		$totalGastos += $total;
        /*Alinear a la izquierda para la cantidad y el nombre*/
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("  ".strtr( $gasto["descripcion"], $unwanted_array ). "\n");

        /*Y a la derecha para el importe*/
        $printer->setJustification(Printer::JUSTIFY_RIGHT);$printer->text(' $' . number_format($total, 0, ',', '.') ."\n");
		$printer->text("- - - - - - - - - - - - - - - - - - - - - - - -\n");
	}
	
	$printer->text("TOTAL EGRESOS :" . ' $' . number_format($totalGastos, 0, ',', '.') . "\n");

   
    /*Alimentamos el papel 3 veces*/
    $printer->feed(2);
	
	$printer->selectPrintMode();
	$printer->text("------------------------------------------------\n");
	$printer->feed(1);
	$printer->setJustification(Printer::JUSTIFY_CENTER);
	$printer->text("RESUMEN\n");
	$printer->feed(1);
	$printer->selectPrintMode();
    $printer->setEmphasis(true);
    $printer->text("       CONCEPTO                     VALOR    \n");
    $printer->text("------------------------------------------------\n");
	$printer->text("    EGRESO :" . '                  $' . number_format($totalGastos, 0, ',', '.') . "\n");
	$printer->text("   INGRESO :" . '                  $' . number_format($totalVentas, 0, ',', '.') . "\n");
	$printer->text("- - - - - - - - - - - - - - - - - - - - - - - -\n");
	$printer->text("    TOTAL  :" . '                  $' . number_format(($totalVentas-$totalGastos), 0, ',', '.') . "\n");
	$printer->text("------------------------------------------------\n");
	
    /*
        Cortamos el papel. Si nuestra impresora
        no tiene soporte para ello, no generará
        ningún error
    */
    $printer->cut();

    /*
        Por medio de la impresora mandamos un pulso.
        Esto es útil cuando la tenemos conectada
        por ejemplo a un cajón
    */
    // $printer->pulse();

    /*
        Para imprimir realmente, tenemos que "cerrar"
        la conexión con la impresora. Recuerda incluir esto al final de todos los archivos
    */
    $printer->close();
}
?>