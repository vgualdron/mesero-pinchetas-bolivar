<?php

require __DIR__ . '/ticket/autoload.php'; 
//Nota: si renombraste la carpeta a algo diferente de "ticket" cambia el nombre en esta línea
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

date_default_timezone_set('America/Bogota');

function printCommand($frm, $type) {
    $producto = $frm;
    $mesa = $frm['mesa'];
    $pedido = $frm['pedido'];
    $flagBebidas = false;
    $flagKiosko = false;
    /*
        Aquí, en lugar de "POS-58" (que es el nombre de mi impresora)
        escribe el nombre de la tuya. Recuerda que debes compartirla
        desde el panel de control
    */
    $idTipoProducto = $producto["idtipoproducto"];
    if ($idTipoProducto == 3) {
        $flagBebidas = true;
    }
    if ($idTipoProducto == 6) {
        $flagKiosko = true;
    }
    if ($flagBebidas) {
        printTicket($mesa, $producto, $pedido, $type, "BEBIDAS-PRINTER");
    }
    if ($flagKiosko) {
        printTicket($mesa, $producto, $pedido, $type, "KIOSCO-PRINTER");
    }
    printTicket($mesa, $producto, $pedido, $type, "POS-80");
}

function printTicket($mesa, $producto, $pedido, $type, $printerName) {
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
    $printer->setEmphasis(true);
    $printer->setTextSize(4,4);
    $printer->text($mesa["descripcion"] . "\n");
    $printer->selectPrintMode();
	$printer->setTextSize(1,1);
	$printer->text("MESERO: " . $mesa["mesero"] . "\n");
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
    if ($type == 'ADD') {
        $printer->text("SE ADICIONA \n");
    } else if ($type == 'CANCEL') {
        $printer->text("SE CANCELA \n");
    } else if ($type == 'EDIT') {
        $productoViejo = $producto['productoviejo'];
        $printer->text("MODIFICAMOS \n");
        $printer->feed(1);
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->setTextSize(1,1);
        $printer->setEmphasis(true);
        $printer->text("  ".$productoViejo["cantidadproducto"]. "   ".strtr( $productoViejo["descripcionproducto"], $unwanted_array ). "\n");
        if (!empty($productoViejo["descripcion"])) {
            $printer->text("       ".strtr($productoViejo["descripcion"], $unwanted_array ). "\n");   
        }
        $printer->setTextSize(2,2);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("------------------------\n");
        $printer->text("POR \n");
    }
    $printer->feed(1);

    $printer->setTextSize(1,1);
    $printer->setEmphasis(true);
    /*Alinear a la izquierda para la cantidad y el nombre*/
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->text("  ".$producto["cantidadproducto"]. "   ".strtr( $producto["descripcionproducto"], $unwanted_array ). "\n");
    if (!empty($producto["descripcion"])) {
        $printer->text("       ".strtr($producto["descripcion"], $unwanted_array ). "\n");   
    }

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