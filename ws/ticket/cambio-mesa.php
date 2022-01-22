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
    $idMesaNueva = $frm['idmesa'];
    $idMesaVieja = $frm['idmesaVieja'];

    $use = $conexion->prepare(" SELECT * FROM pinchetas_restaurante.mesa mesa
        WHERE mesa.mesa_id = ?; ");
    $use->bindValue(1, $idMesaNueva);
    $use ->execute();
    $row = $use->fetch();
    $mesaNueva = $row['mesa_descripcion'];

    $use = $conexion->prepare(" SELECT * FROM pinchetas_restaurante.mesa mesa
        WHERE mesa.mesa_id = ?; ");
    $use->bindValue(1, $idMesaVieja);
    $use ->execute();
    $row = $use->fetch();
    $mesaVieja = $row['mesa_descripcion'];

    echo $mesaNueva;
    echo $mesaVieja;

    printCommand($mesaNueva, $mesaVieja, $nombreMesero, "POS-80");
}

function printCommand($mesaNueva, $mesaVieja, $mesero, $printerName) {
    $unwanted_array = array('Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A',                                     'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
        'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
        'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
        'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
        'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );

    $nombre_impresora = $printerName;

    $connector = new WindowsPrintConnector($nombre_impresora);
    $printer = new Printer($connector);

    $printer->feed(5);
    # Vamos a alinear al centro lo próximo que imprimamos
    $printer->setJustification(Printer::JUSTIFY_CENTER);

    /*
        Ahora vamos a imprimir un encabezado
    */
    $printer->selectPrintMode();
    $printer->setTextSize(1,1);
    $printer->text("MESERO: " . $mesero . "\n");
    $printer->selectPrintMode();
    // $printer->text("Otra linea" . "\n");
    #La fecha también
    $printer->text(date("d-m-Y H:i:s") . "\n");

    /*
        Ahora vamos a imprimir los
        productos
    */
    $printer->setTextSize(2,2);
    $printer->text("------------------------\n");

    $printer->text("SE CAMBIA LA MESA \n");
    $printer->feed(1);
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->setEmphasis(true);
    $printer->text(strtr($mesaVieja, $unwanted_array ). "\n");
    $printer->feed(2);
    $printer->setTextSize(2,2);
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("------------------------\n");
    $printer->text("POR LA MESA \n");
    $printer->feed(1);
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->setEmphasis(true);
    $printer->text(strtr($mesaNueva, $unwanted_array ). "\n");
    $printer->feed(1);

    $printer->setTextSize(2,2);
    $printer->setEmphasis(true);
    $printer->text("------------------------\n");
    $printer->selectPrintMode();

    $printer->setJustification(Printer::JUSTIFY_RIGHT);
    $printer->text("Pinchetas.\n");

    $printer->feed(1);
    $printer->cut();
    $printer->close();
}

?>