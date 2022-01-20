<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
require __DIR__ . '/ticket/autoload.php'; 
//Nota: si renombraste la carpeta a algo diferente de "ticket" cambia el nombre en esta línea
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

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

    /*
        Aquí, en lugar de "POS-58" (que es el nombre de mi impresora)
        escribe el nombre de la tuya. Recuerda que debes compartirla
        desde el panel de control
    */

    $nombre_impresora = "POS-80"; 


    $connector = new WindowsPrintConnector($nombre_impresora);
    $printer = new Printer($connector);



    $printer->pulse();

   
    $printer->close();

?>