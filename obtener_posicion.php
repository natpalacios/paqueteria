<?php

require_once __DIR__ . '/conexion.php';

header('Content-Type: application/json; charset=utf-8');

$envio_id = isset($_GET['envio_id'])
    ? (int) $_GET['envio_id']
    : 0;

if ($envio_id <= 0) {

    echo json_encode([
        'ok' => false,
        'mensaje' => 'ID inválido'
    ]);

    exit;
}


$sql = "
    SELECT
        envio_id,
        latitud,
        longitud,
        velocidad,
        rumbo,
        fecha_hora
    FROM posiciones_gps
    WHERE envio_id = ?
    ORDER BY fecha_hora DESC
    LIMIT 1
";

$stmt = $conexion->prepare($sql);

$stmt->bind_param(
    "i",
    $envio_id
);

$stmt->execute();

$resultado = $stmt->get_result();

$posicion = $resultado->fetch_assoc();

$stmt->close();


if (!$posicion) {

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No existe posición GPS'
    ]);

    exit;
}


echo json_encode([
    'ok' => true,
    'posicion' => $posicion
]);

$conexion->close();