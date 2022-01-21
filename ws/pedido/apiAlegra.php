<?php
// Method: POST, PUT, GET etc
// Data: array("param" => "value") ==> index.php?param=value
require_once("../conexion.php");

class ApiAlegra extends PDO {
	public $urlApi;
	public $categories;
	public $preReference;
	public $conexion;
	
	public function __construct() {
		$this->categories  =  array(
			"BEBIDAS" => 3,
			"PINCHETAS" => 1,
			"ADICIONALES" => 2,
			"CORTES DE CARNE" => 5,
			"ENTRADAS" => 4
		);
		$this->preReference = 'PINCHETAS_BOLIVAR_';
		$this->urlApi = 'https://api.alegra.com/api/v1';
		$this->conexion = new Conexion();
	}
	
	public function getCategories() {
		return $this->categories;
	}
	
	public function getIdCategory($name) {
		return $this->getCategories()[$name];
	}
	
	public function getPreReference() {
		return $this->preReference;
	}
	
	public function getUrlApi() {
		return $this->urlApi;
	}
	
	public function getConexion() {
		return $this->conexion;
	}
	
	public function callApi($method, $url, $data = false) {
		$curl = curl_init();

		switch ($method) {
			case "POST":
				curl_setopt($curl, CURLOPT_POST, 1);
				if ($data) {
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				}
				break;
			case "PUT":
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
				if ($data) {
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				}
         		break;
			default:
				if ($data) {
					$url = sprintf("%s?%s", $url, http_build_query($data));
				}
		}

		// Optional Authentication:
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_USERPWD, "pinchetasbolivar@hotmail.com:4c31f9d11aa9b452be95");

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);

		$result = curl_exec($curl);

		curl_close($curl);

		return $result;
	}

	public function getItem($data) {
		$name = str_replace (' ', '%20', $data['name']);
		$reference = $data['reference'];
		$url = $this->getUrlApi().'/items?reference='.$reference.'&name='.$name;
		$make_call = $this->callApi('GET', $url);
		$response = json_decode($make_call, true);
		return $response;
	}
	
	public function insertItem($data) {
		$url = $this->getUrlApi().'/items';
		$make_call = $this->callApi('POST', $url, json_encode($data));
		$response = json_decode($make_call, true);
		return $response;
	}
	
	public function updateItem($data) {
		$url = $this->getUrlApi()."/items"."/".$data["id"];
		unset($data['id']);
		$make_call = $this->callApi('PUT', $url, json_encode($data));
		$response = json_decode($make_call, true);
		return $response;
	}

	public function makeItem($data) {
		$idPinchetas = $data["idPinchetas"];
		$idAlegra = $data["id"];
		$depeQuantity = $data["depeQuantity"];
		$name = $data["name"];
		$nameTipoProducto = $data["nameTipoProducto"];
		$reference = $this->getPreReference().$idPinchetas;
		$price = $data["price"];
		$unitCost = $data["cost"];
		$unit = "unit";
		$type = "simple";
		$idWarehouse = $this->makeWarehouseInvoice(null);
		$initialQuantity = $data["quantity"] ? $data["quantity"] : 100;
		$minQuantity = 10;
		$maxQuantity = 1000;
		$idCategory = $this->getIdCategory($nameTipoProducto);
		
		$item = array(
      		"idPinchetas" => $idPinchetas,
			"id" => $idAlegra,
			"name" => $name,
			"price" => $price,
			"reference" => $reference,
			"quantity" => $depeQuantity,
			"type" =>  $type,
			"itemCategory" => array(
				"id" => 1
			),
			"inventory" => array(
				"unit" => $unit,
				"unitCost" => $unitCost,
				"warehouses" => array(
					array(
						"id" => $idWarehouse,
						"initialQuantity" => $initialQuantity,
						"minQuantity" => $minQuantity,
						"maxQuantity" => $maxQuantity
					)
				)
			)
		);
		
		return $item;
	}

	public function makeItemsInvoice($idPedido) {
		$conexion = $this->getConexion();

		$sql = $conexion->prepare(" select 
			prod.prod_id as id, 
			prod.prod_descripcion as name, 
			prod.prod_precio as price, 
			prod.prod_costo as cost,
			prod.prod_cantidad as quantity,
			depe.prod_cantidad as depeQuantity,
			tipr.tipr_descripcion as nameTipoProducto
			FROM pinchetas_restaurante.detallepedido depe
			INNER JOIN pinchetas_restaurante.producto prod ON (prod.prod_id = depe.prod_id)
			INNER JOIN pinchetas_restaurante.tipoproducto tipr ON (tipr.tipr_id = prod.tipr_id)
			WHERE pedi_id = ?
			ORDER BY depe.depe_fechacambio; ");

		$sql->bindValue(1, $idPedido);
		$sql->execute();
		$items = $sql->fetchAll();

		$itemsForInvoice = [];

		foreach ($items as $clave => $valor) {
			$itemNew = $this->makeItem($valor);
			$itemNew["idPinchetas"] = $valor["id"];
			$response = $this->getItem($itemNew);
			if(empty($response)) {
				$response["idPinchetas"] = $valor["id"];
				$response["name"] = $valor["name"];
				$response["price"] = $valor["price"];
				$response["quantity"] = $valor["quantity"];
				$response["depeQuantity"] = $valor["depeQuantity"];
				$response["cost"] = $valor["cost"];
				$itemNew = $this->makeItem($response);
				$response = $this->insertItem($itemNew);
				$itemNew["id"] = $response["id"];
			} else {
				$response = $response[0];
				$response["idPinchetas"] = $valor["id"];
				$response["price"] = $valor["price"];
				$response["quantity"] = $valor["quantity"];
				$response["depeQuantity"] = $valor["depeQuantity"];
				$response["cost"] = $valor["cost"];
				$itemNew = $this->makeItem($response);
				$response = $this->updateItem($itemNew);
			}
			$itemsForInvoice[] = $itemNew;
		}

		return $itemsForInvoice;
	}
	
	public function makePaymentsInvoice($data) {
		
		$conexion = $this->getConexion();
		
		$sql = $conexion->prepare(" SELECT
		SUM(depe.prod_precio * depe.prod_cantidad) as amount,
		pedi.pedi_tipopago as paymentMethod
		FROM pinchetas_restaurante.detallepedido depe
		INNER JOIN pinchetas_restaurante.pedido pedi ON (depe.pedi_id = pedi.pedi_id)
		WHERE depe.pedi_id = ?; ");

        $sql->bindValue(1, $data["idPedido"]);
        $sql->execute();
		$result = $sql->fetch(PDO::FETCH_ASSOC);

		$payments = array(
			array(
				"date" => $data["date"],
				"account" => array(
					"id" => $data["idAccount"]
				),
				"amount" => $result["amount"],
				"paymentMethod" => $result["paymentMethod"] == 'EFECTIVO' ? 'cash' : 'credit-card',
				"anotations" => "",
				"observations" => ""
			)
		);
		return $payments;
	}

	public function getClient($identificationNumber) {
		$url = $this->getUrlApi().'/contacts/?type=client&identification='.$identificationNumber;
		$make_call = $this->callApi('GET', $url, false);
		$response = json_decode($make_call, true);
		return $response;
	}

	public function insertClient($data) {
		$url = $this->getUrlApi().'/contacts';
		$make_call = $this->callApi('POST', $url, json_encode($data));
		$response = json_decode($make_call, true);
		return $response;
	}

	public function makeClientInvoice($data) {
		if($data["id"]) {
			return $data["id"];
		} else {
			$response = $this->insertClient($data);
			return $response["id"];
		}

	}

	public function makeWarehouseInvoice($idPedido) {
		return 1;  // warehouse de sayago
	}

	public function makeStampInvoice($data) {
	
		$invoice = array(
			"date" => $data["date"],
			"dueDate" => $data["date"],
			"client" => $data["client"],
			"items" => $data["items"],
			"payments" => $data["payments"],
			"warehouse" => $data["warehouse"],
			"paymentForm" => $data["paymentForm"],
        	"paymentMethod" => $data["paymentMethod"] == 'EFECTIVO' ? 'CASH' : 'CREDIT_CARD',
			"stamp" => array(
				"generateStamp" => true
			)
		);

		return $invoice;
	}

	public function stampInvoice($data) {
		$url = $this->getUrlApi()."/invoices";
		// unset($data['id']);
		$make_call = $this->callApi('POST', $url, json_encode($data));
		$response = json_decode($make_call, true);
		return $response;
	}
}
?>