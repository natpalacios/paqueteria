<?php

require_once "conexion.php";

$departamento_id = intval($_GET["departamento_id"] ?? 0);

if ($departamento_id <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id, nombre
        FROM municipios
        WHERE departamento_id = ?
        ORDER BY nombre ASC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $departamento_id);
$stmt->execute();

$resultado = $stmt->get_result();

$municipios = [];

while ($fila = $resultado->fetch_assoc()) {
    $municipios[] = $fila;
}

header("Content-Type: application/json; charset=UTF-8");

echo json_encode($municipios, JSON_UNESCAPED_UNICODE);

$stmt->close();
$conexion->close();

?>

