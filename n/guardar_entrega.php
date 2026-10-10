<?php
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["ok" => false, "mensaje" => "Método no permitido."]);
    exit;
}

$carpeta = __DIR__ . DIRECTORY_SEPARATOR . "datos";
if (!is_dir($carpeta)) {
    mkdir($carpeta, 0777, true);
}

$entrega = [
    "idEntrega" => $_POST["idEntrega"] ?? "",
    "fechaHora" => $_POST["fechaHora"] ?? "",
    "destino" => $_POST["destino"] ?? "",
    "direccion" => $_POST["direccion"] ?? "",
    "paquete" => $_POST["paquete"] ?? "",
    "conductor" => $_POST["conductor"] ?? "",
    "presenciaCliente" => $_POST["presenciaCliente"] ?? "",
    "nombreReceptor" => $_POST["nombreReceptor"] ?? "",
    "cedula" => $_POST["cedula"] ?? "",
    "estadoPaquete" => $_POST["estadoPaquete"] ?? "",
    "motivo" => $_POST["motivo"] ?? "",
    "observaciones" => $_POST["observaciones"] ?? "",
    "estadoFinal" => $_POST["estadoFinal"] ?? "",
    "fechaRegistro" => date("Y-m-d H:i:s")
];

$archivoJson = $carpeta . DIRECTORY_SEPARATOR . "entregas.json";
$registros = [];

if (file_exists($archivoJson)) {
    $contenido = file_get_contents($archivoJson);
    $registros = json_decode($contenido, true) ?: [];
}

$registros[] = $entrega;
file_put_contents($archivoJson, json_encode($registros, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if (!empty($_POST["firma"])) {
    $firma = str_replace("data:image/png;base64,", "", $_POST["firma"]);
    $firma = str_replace(" ", "+", $firma);
    file_put_contents($carpeta . DIRECTORY_SEPARATOR . $entrega["idEntrega"] . "_firma.png", base64_decode($firma));
}

if (isset($_FILES["fotoPaquete"]) && $_FILES["fotoPaquete"]["error"] === UPLOAD_ERR_OK) {
    $extension = pathinfo($_FILES["fotoPaquete"]["name"], PATHINFO_EXTENSION);
    $nombreFoto = $entrega["idEntrega"] . "_foto." . $extension;
    move_uploaded_file($_FILES["fotoPaquete"]["tmp_name"], $carpeta . DIRECTORY_SEPARATOR . $nombreFoto);
}

echo json_encode([
    "ok" => true,
    "mensaje" => "Entrega guardada correctamente en el archivo JSON."
]);
?>
