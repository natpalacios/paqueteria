<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../conexion.php";

$accion = $_GET["accion"] ?? "";

if ($accion === "envios") {

    $sql = "
        SELECT
            e.id,
            e.codigo,
            e.estado,
            e.fecha_envio,
            e.fecha_entrega,
            e.departamento,
            e.municipio,
            e.direccion,
            c.nombre AS cliente,
            r.nombre AS repartidor,
            r.codigo AS codigo_repartidor,
            r.vehiculo
        FROM envios e
        LEFT JOIN clientes c
            ON e.cliente_id = c.id
        LEFT JOIN repartidores r
            ON e.repartidor_id = r.id
        ORDER BY e.id ASC
    ";

    $resultado = $conn->query($sql);

    if (!$resultado) {
        echo json_encode([
            "error" => $conn->error
        ]);
        exit;
    }

    $envios = [];

    while ($fila = $resultado->fetch_assoc()) {
        $envios[] = $fila;
    }

    echo json_encode($envios, JSON_UNESCAPED_UNICODE);
    exit;
}


if ($accion === "gps") {

    $envioId = isset($_GET["envio_id"])
        ? intval($_GET["envio_id"])
        : 0;

    if ($envioId <= 0) {
        echo json_encode([
            "error" => "ID de envío no válido"
        ]);
        exit;
    }

    $sql = "
        SELECT
            id,
            envio_id,
            latitud,
            longitud,
            velocidad,
            precision_gps,
            fecha_hora
        FROM gps_posiciones
        WHERE envio_id = ?
        ORDER BY fecha_hora ASC
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            "error" => $conn->error
        ]);
        exit;
    }

    $stmt->bind_param("i", $envioId);
    $stmt->execute();

    $resultado = $stmt->get_result();

    $gps = [];

    while ($fila = $resultado->fetch_assoc()) {
        $fila["latitud"] = floatval($fila["latitud"]);
        $fila["longitud"] = floatval($fila["longitud"]);
        $fila["velocidad"] = floatval($fila["velocidad"]);

        $gps[] = $fila;
    }

    echo json_encode($gps, JSON_UNESCAPED_UNICODE);
    exit;
}


echo json_encode([
    "error" => "Acción no válida"
]);