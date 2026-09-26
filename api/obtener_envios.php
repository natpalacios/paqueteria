<?php

header('Content-Type: application/json; charset=utf-8');

require_once "../conexion.php";

try {

    /*
     * Obtenemos los envíos y relacionamos:
     *
     * envios.cliente_id → clientes.id
     * envios.repartidor_id → repartidores.id
     */

    $sql = "
        SELECT
            e.id,
            e.codigo,
            c.nombre AS cliente,
            r.nombre AS repartidor,
            e.origen,
            e.destino,
            e.descripcion,
            e.estado,
            e.fecha_envio,
            e.fecha_entrega

        FROM envios e

        INNER JOIN clientes c
            ON e.cliente_id = c.id

        LEFT JOIN repartidores r
            ON e.repartidor_id = r.id

        ORDER BY e.fecha_envio DESC
    ";


    $resultado = $conexion->query($sql);

    if (!$resultado) {
        throw new Exception($conexion->error);
    }


    $envios = [];


    while ($fila = $resultado->fetch_assoc()) {

        $envios[] = [
            "id" => (int)$fila["id"],
            "codigo" => $fila["codigo"],
            "cliente" => $fila["cliente"],
            "repartidor" => $fila["repartidor"],
            "origen" => $fila["origen"],
            "destino" => $fila["destino"],
            "descripcion" => $fila["descripcion"],
            "estado" => $fila["estado"],
            "fecha_envio" => $fila["fecha_envio"],
            "fecha_entrega" => $fila["fecha_entrega"]
        ];
    }


    echo json_encode([
        "success" => true,
        "envios" => $envios
    ], JSON_UNESCAPED_UNICODE);


} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" => "Error al consultar los envíos: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

}


if (isset($conexion)) {
    $conexion->close();
}

?>