<?php

require_once __DIR__ . '/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido'
    ]);

    exit;
}

$envio_id = isset($_POST['envio_id'])
    ? (int) $_POST['envio_id']
    : 0;

$latitud = isset($_POST['latitud'])
    ? (float) $_POST['latitud']
    : 0;

$longitud = isset($_POST['longitud'])
    ? (float) $_POST['longitud']
    : 0;

$velocidad = isset($_POST['velocidad'])
    ? (float) $_POST['velocidad']
    : 0;

$precision_gps = isset($_POST['precision_gps'])
    ? (float) $_POST['precision_gps']
    : null;


if ($envio_id <= 0) {

    echo json_encode([
        'ok' => false,
        'mensaje' => 'ID de envío inválido'
    ]);

    exit;
}


if ($latitud == 0 && $longitud == 0) {

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Coordenadas inválidas'
    ]);

    exit;
}



$sql = "
    SELECT id, estado
    FROM envio
    WHERE id = ?
    LIMIT 1
";

$stmt = $conexion->prepare($sql);

$stmt->bind_param("i", $envio_id);

$stmt->execute();

$resultado = $stmt->get_result();

$envio = $resultado->fetch_assoc();

$stmt->close();


if (!$envio) {

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El envío no existe'
    ]);

    exit;
}


$estado = strtolower(trim($envio['estado']));


if ($estado !== 'en transito' &&
    $estado !== 'en tránsito') {

    echo json_encode([
        'ok' => false,
        'mensaje' => 'El envío no está en tránsito'
    ]);

    exit;
}



$sql = "
    INSERT INTO posiciones_gps
    (
        envio_id,
        latitud,
        longitud,
        velocidad,
        precision_gps,
        fecha_hora
    )
    VALUES (?, ?, ?, ?, ?, NOW())
";

$stmt = $conexion->prepare($sql);

$stmt->bind_param(
    "idddd",
    $envio_id,
    $latitud,
    $longitud,
    $velocidad,
    $precision_gps
);


if ($stmt->execute()) {

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Posición GPS guardada'
    ]);

} else {

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No se pudo guardar la posición'
    ]);
}


$stmt->close();

$conexion->close();