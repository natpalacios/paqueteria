<?php

require_once "conexion.php";

$municipio_id = intval($_GET["municipio_id"] ?? 0);
$peso = floatval($_GET["peso"] ?? 0);

if ($municipio_id <= 0 || $peso <= 0) {

    echo json_encode([
        "success" => false,
        "mensaje" => "Datos inválidos"
    ]);

    exit;
}

$sql = "SELECT precio
        FROM tarifas
        WHERE municipio_id = ?
        AND ? BETWEEN peso_min AND peso_max
        LIMIT 1";

$stmt = $conexion->prepare($sql);

$stmt->bind_param(
    "id",
    $municipio_id,
    $peso
);

$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {

    echo json_encode([
        "success" => false,
        "mensaje" => "No existe una tarifa para este peso"
    ]);

    exit;
}

$fila = $resultado->fetch_assoc();

echo json_encode([
    "success" => true,
    "precio" => floatval($fila["precio"])
]);

$stmt->close();
$conexion->close();

?>

