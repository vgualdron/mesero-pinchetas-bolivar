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

$frm = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $mesa = $frm['mesa'];
    $numeroMesa = substr($mesa['descripcion'], 4);
	
	$mystring = $mesa['descripcion'];
	$findme = 'PINCHELADAS';
	
	$pos = strpos($mystring, $findme);
    $tienePropina = false;

    if (strpos($mystring, 'DOMICILIO') === false && strpos($mystring, 'DE LLEVAR') === false) {
        $tienePropina = true;
    }
	
	/* if ($pos === false) {
		if ($numeroMesa >= 25 && $numeroMesa <= 45) {
			printInvoice($frm, 'SEGUNDO-PISO-PRINTER', $tienePropina);
		} else {
			printInvoice($frm, 'POS-80', $tienePropina);
		}
	} else {
		printInvoice($frm, 'KIOSCO-PRINTER', $tienePropina);
	} */

    printInvoice($frm, 'POS-80', $tienePropina);
	
}

function printInvoice($frm, $printerName, $tienePropina = false) {
    $conexion = new Conexion();
    $conexion ->query("SET NAMES 'utf8';");

    $unwanted_array = array('Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A',                                     'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
    
    $nombre_impresora = $printerName;

    $connector = new WindowsPrintConnector($nombre_impresora);
    $printer = new Printer($connector);

    # Vamos a alinear al centro lo próximo que imprimamos
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $idpena = openCypher('decrypt', $frm['token']);
    $tipoPago = $frm['tipopago'];
    $fechaFactura = $frm['fecha'];

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
    $nombreCajero = $row['pena_primernombre']. " " . $row['pena_primerapellido'];

    $fecha = date("Y-m-d");
    $hora = date("H:i:s");

    $productos = $frm['productos'];
    $mesa = $frm['mesa'];
	$mesero = strtoupper($frm['mesero']);
	// $prefijoFactura = $frm['prefijofactura'];
	$numeroFactura = $frm['numerofactura'];
	$nombreCliente = $frm['nombrecliente'];
	$direccionCliente = $frm['direccioncliente'];
	$telefonoCliente = $frm['telefonocliente'];

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
    $printer->text($nombreEmpresa . "\n");
    $printer->setTextSize(1,1);
    $printer->text("NIT: " . $nitEmpresa . "\n");
    $printer->text($direccionEmpresa . "\n");
    $printer->text("TEL: " . $telefonoEmpresa . "\n");
    $printer->text($resolucionEmpresa . " \n");
    $printer->text("" . $rangoAutorizadoFacturas . "\n");
    
    $printer->feed(1);
    $printer->setTextSize(1,1);
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    if (!empty($numeroFactura)) {
        $printer->text("FACTURA VENTA No.  : " . $prefijoFactura. " " . $numeroFactura . "\n");
    }
    
    if (empty($fechaFactura)) {
        $printer->text("FECHA     : " . $fecha. " HORA " . $hora . "\n");
    } else {
        $printer->text("FECHA     : " . $fechaFactura. "\n");
    }
    
    $printer->text("CAJA      : " . $prefijoFactura . " 1\n");
    $printer->text("MESA      : " . $mesa['descripcion'] . "\n");
    $printer->text("CAJERO    : " . $nombreCajero . "\n");
    if (!empty($nombreCliente)) {
    $printer->text("CLIENTE   : " . $nombreCliente . "\n");
    $printer->text("TELEFONO  : " . $telefonoCliente . "\n");
    $printer->text("DIRECCION : " . $direccionCliente . "\n");
    } else {
    $printer->text("CLIENTE   : VARIOS\n");
    }
    $printer->selectPrintMode();
    // $printer->text("Otra linea" . "\n");
    #La fecha también
    

    $printer->selectPrintMode();
    $printer->text("------------------------------------------------\n");
    $printer->setEmphasis(true);
    $printer->text("CANT                DESCRIPCION           PRECIO\n");
    $printer->selectPrintMode();
    $printer->text("------------------------------------------------\n");
    
    /*
        Ahora vamos a imprimir los
        productos
    */

    # Para mostrar el total
    $total = 0;
    $totalCantidadProductos = 0;
    foreach ($productos as $clave => $producto) {
        $total += $producto["cantidadproducto"] * $producto["precioproducto"];
        $totalCantidadProductos = $totalCantidadProductos + $producto["cantidadproducto"];
        /*Alinear a la izquierda para la cantidad y el nombre*/
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("  ".substr($producto["cantidadproducto"],0,22). "   ".strtr( $producto["descripcionproducto"], $unwanted_array ). "\n");

        /*Y a la derecha para el importe*/
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        // $printer->text(' $' . number_format($producto["precioproducto"], 0, ',', '.') . '   $' . number_format(($producto["cantidadproducto"])*($producto["precioproducto"]), 0, ',', '.') ."\n");
        $printer->setTextSize(2,1);
        $printer->setEmphasis(false);
        $printer->text(' $' . number_format(($producto["cantidadproducto"])*($producto["precioproducto"]), 0, ',', '.') ."\n");
        $printer->selectPrintMode();
    }
	
	$propina = 0;

    /*
        Terminamos de imprimir
        los productos, ahora va el total
    */
    $printer->text("------------------------------------------------\n");
    $printer->setEmphasis(true);
    $printer->setJustification(Printer::JUSTIFY_RIGHT);
    $printer->setTextSize(1,1);
    $printer->setEmphasis(false);
	
    if ($tienePropina === true) {
        $propina = $total * 0.10;
        $printer->text("SUBTOTAL: $".  number_format($total, 0, ',', '.') ."\n");
        $printer->text("PROPINA SUGERIDA: $".  number_format($propina, 0, ',', '.') ."\n");
    }

	$printer->setEmphasis(true);
	$printer->setTextSize(2,2);
	$printer->text("TOTAL: $".  number_format($total + $propina, 0, ',', '.') ."\n");
	
    $printer->selectPrintMode();
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->text("CANTIDAD PRODUCTOS: ". $totalCantidadProductos ."\n");
    $printer->text("------------------------------------------------\n");
    
    $printer->text("    BASE      %          IVA      %       ICO   \n");
    
    // $base = $total - ($total * 0.08);
    $base = $total;
    $iva = 0;
    // $ico = ($total * 0.08);
    $ico = ($total * 0);
    $printer->text(number_format($base, 2, ',', '.') ."    0 " . "         0.00    " . "  8   " . number_format($ico, 2, ',', '.') . "\n");
    $printer->text("-------------  ----------------  ---------------\n");
    $printer->setEmphasis(true);
    $printer->text(number_format($base, 2, ',', '.') ."      " . "         0.00    " . "      " . number_format($ico, 2, ',', '.') . "\n");
    $printer->selectPrintMode();
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("Forma de Pago  \n");
    $printer->setJustification(Printer::JUSTIFY_RIGHT);
    $printer->text($tipoPago . "           $".  number_format($total, 0, ',', '.') ."\n");

    $printer->selectPrintMode();
    $printer->text("------------------------------------------------\n");

    $printer->text("Estamos para servirle. Gracias por su compra.\n");

    $printer->feed(2);
    $printer->cut();
    $printer->close();
}
?>