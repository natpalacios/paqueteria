<?php

require_once "conexion.php";

header("Content-Type: application/json; charset=UTF-8");

$municipio_id = intval($_GET["municipio_id"] ?? 0);

if ($municipio_id <= 0) {

    echo json_encode([
        "success" => false,
        "mensaje" => "Municipio inválido"
    ]);

    exit;
}



$sql = "SELECT precio
        FROM tarifas
        WHERE municipio_id = ?
        ORDER BY peso_min ASC
        LIMIT 1";

$stmt = $conexion->prepare($sql);

if (!$stmt) {

    echo json_encode([
        "success" => false,
        "mensaje" => "Error preparando la consulta"
    ]);

    exit;
}

$stmt->bind_param(
    "i",
    $municipio_id
);

$stmt->execute();

$resultado = $stmt->get_result();


if ($resultado->num_rows === 0) {

    echo json_encode([
        "success" => false,
        "mensaje" => "No existe una tarifa para este municipio"
    ]);

    $stmt->close();
    $conexion->close();

    exit;
}


$fila = $resultado->fetch_assoc();

$costoEnvio = floatval($fila["precio"]);



echo json_encode([
    "success" => true,
    "precio" => $costoEnvio,
    "precio_kg" => 100
]);


$stmt->close();
$conexion->close();

?>