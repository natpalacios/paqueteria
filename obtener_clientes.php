<?php

require_once "conexion.php";

$sql = "SELECT id, nombre, cedula, telefono
        FROM clientes
        ORDER BY nombre ASC";

$resultado = $conexion->query($sql);

$clientes = [];

while ($fila = $resultado->fetch_assoc()) {
    $clientes[] = $fila;
}

header("Content-Type: application/json; charset=UTF-8");

echo json_encode($clientes, JSON_UNESCAPED_UNICODE);

$conexion->close();

?>


