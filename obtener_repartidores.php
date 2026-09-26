<?php

require_once "conexion.php";

$sql = "SELECT id, nombre, codigo, vehiculo, departamento, estado
        FROM repartidores
        WHERE estado = 'Activo'
        ORDER BY nombre ASC";

$resultado = $conexion->query($sql);

$repartidores = [];

while ($fila = $resultado->fetch_assoc()) {
    $repartidores[] = $fila;
}

header("Content-Type: application/json; charset=UTF-8");

echo json_encode($repartidores, JSON_UNESCAPED_UNICODE);

$conexion->close();

?>

