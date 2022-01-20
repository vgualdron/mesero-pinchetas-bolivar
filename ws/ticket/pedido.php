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

require_once("../conexion.php");
require_once("../encrypted.php");

$conexion = new Conexion();
$conexion ->query("SET NAMES 'utf8';");

$frm = json_decode(file_get_contents('php://input'), true);

$idpena = openCypher('decrypt', $frm['token']);

$use = $conexion->prepare(" SELECT * FROM pinchetas_general.personanatural pena
							WHERE pena.pege_id = ?; ");

$use->bindValue(1, $idpena);
$use ->execute();
$row = $use->fetch();
$nombreMesero = $row['pena_primernombre']. " " . $row['pena_primerapellido'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $productos = $frm['productos'];
    $mesa = $frm['mesa'];
    $mesa["nombreMesero"] = $nombreMesero;
    $flagBebidas = false;
    $flagKiosko = false;
    /*
        Aquí, en lugar de "POS-58" (que es el nombre de mi impresora)
        escribe el nombre de la tuya. Recuerda que debes compartirla
        desde el panel de control
    */
    foreach ($productos as $clave => $producto) {
        $idTipoProducto = $producto["idtipoproducto"];
        if ($idTipoProducto == 3) {
            $flagBebidas = true;
        }
        if ($idTipoProducto == 6) {
            $flagKiosko = true;
        }
    }
    if ($flagBebidas) {
        printCommand($mesa, $productos, "BEBIDAS-PRINTER");
    }
    if ($flagKiosko) {
        printCommand($mesa, $productos, "KIOSCO-PRINTER");
    }
    printCommand($mesa, $productos, "POS-80");
}

function printCommand($mesa, $productos, $printerName) {
    $unwanted_array = array('Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A',                                     'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
        'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
        'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
        'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
        'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );

    $nombre_impresora = $printerName; 

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
    $printer->feed(5);
    # Vamos a alinear al centro lo próximo que imprimamos
    $printer->setJustification(Printer::JUSTIFY_CENTER);

    /*
        Ahora vamos a imprimir un encabezado
    */
    $printer->setEmphasis(true);
    $printer->setTextSize(4,4);
    $printer->text($mesa["descripcion"] . "\n");
    $printer->selectPrintMode();
	$printer->setTextSize(1,1);
	$printer->text("MESERO: " . $mesa["nombreMesero"] . "\n");
	$printer->selectPrintMode();
    // $printer->text("Otra linea" . "\n");
    #La fecha también
    $printer->text(date("d-m-Y H:i:s") . "\n");
    
    /*
        Ahora vamos a imprimir los
        productos
    */

    # Para mostrar el total
    $total = 0;
    foreach ($productos as $clave => $producto) {
        $total += $producto["cantidadproducto"] * $producto["precioproducto"];
        $printer->text("------------------------------------------------\n");
        $printer->setEmphasis(true);
        /*Alinear a la izquierda para la cantidad y el nombre*/
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("  ".$producto["cantidadproducto"]. "   ".strtr( $producto["descripcionproducto"], $unwanted_array ). "\n");
        if (!empty($producto["descripcion"])) {
          $printer->text("       ".strtr($producto["descripcion"], $unwanted_array ). "\n");   
        }
        $printer->selectPrintMode();
        $printer->text("------------------------------------------------\n");
    }

    /*
        Terminamos de imprimir
        los productos, ahora va el total
    */


    /*
        Podemos poner también un pie de página
    */
    $printer->setJustification(Printer::JUSTIFY_RIGHT);
    $printer->text("Pinchetas.\n");



    /*Alimentamos el papel 3 veces*/
    $printer->feed(1);

    /*
        Cortamos el papel. Si nuestra impresora
        no tiene soporte para ello, no generará
        ningún error
    */
    $printer->cut();

    /*
        Para imprimir realmente, tenemos que "cerrar"
        la conexión con la impresora. Recuerda incluir esto al final de todos los archivos
    */
    $printer->close();
}

?>