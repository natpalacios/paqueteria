<?php

header('Content-Type: application/json; charset=utf-8');

require_once "../conexion.php";

try {

    // Total de envíos
    $sqlTotal = "SELECT COUNT(*) AS total FROM envios";
    $resultadoTotal = $conexion->query($sqlTotal);

    if (!$resultadoTotal) {
        throw new Exception($conexion->error);
    }

    $total = $resultadoTotal->fetch_assoc()["total"];


    // Envíos en tránsito
    $sqlTransito = "
        SELECT COUNT(*) AS total
        FROM envios
        WHERE estado = 'En tránsito'
    ";

    $resultadoTransito = $conexion->query($sqlTransito);

    if (!$resultadoTransito) {
        throw new Exception($conexion->error);
    }

    $transito = $resultadoTransito->fetch_assoc()["total"];


    // Envíos entregados
    $sqlEntregados = "
        SELECT COUNT(*) AS total
        FROM envios
        WHERE estado = 'Entregado'
    ";

    $resultadoEntregados = $conexion->query($sqlEntregados);

    if (!$resultadoEntregados) {
        throw new Exception($conexion->error);
    }

    $entregados = $resultadoEntregados->fetch_assoc()["total"];


    // Envíos pendientes
    $sqlPendientes = "
        SELECT COUNT(*) AS total
        FROM envios
        WHERE estado = 'Pendiente'
    ";

    $resultadoPendientes = $conexion->query($sqlPendientes);

    if (!$resultadoPendientes) {
        throw new Exception($conexion->error);
    }

    $pendientes = $resultadoPendientes->fetch_assoc()["total"];


    // Respuesta
    echo json_encode([
        "success" => true,
        "total" => (int)$total,
        "transito" => (int)$transito,
        "entregados" => (int)$entregados,
        "pendientes" => (int)$pendientes
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => "Error al consultar el dashboard: " . $e->getMessage()
    ]);

}

if (isset($conexion)) {
    $conexion->close();
}

?>