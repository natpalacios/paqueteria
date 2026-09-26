<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION["id"])) {
    echo json_encode([
        "ok" => false,
        "mensaje" => "Sesión no válida."
    ]);
    exit;
}

require_once "conexion.php";

$accion = $_GET["accion"] ?? $_POST["accion"] ?? "";




if ($accion === "dashboard") {

    $datos = [];

    $consulta = $conexion->query("
        SELECT 
            COUNT(*) AS total,
            SUM(estado = 'En tránsito') AS transito,
            SUM(estado = 'Entregado') AS entregados,
            SUM(estado = 'Pendiente') AS pendientes
        FROM envios
    ");

    if ($consulta) {
        $fila = $consulta->fetch_assoc();

        $datos["total"] = (int)($fila["total"] ?? 0);
        $datos["transito"] = (int)($fila["transito"] ?? 0);
        $datos["entregados"] = (int)($fila["entregados"] ?? 0);
        $datos["pendientes"] = (int)($fila["pendientes"] ?? 0);
    }

    echo json_encode([
        "ok" => true,
        "datos" => $datos
    ]);

    exit;
}




if ($accion === "envios") {

    $sql = "
        SELECT
            e.id,
            e.codigo,
            c.nombre AS cliente,
            COALESCE(r.nombre, 'Sin asignar') AS repartidor,
            e.origen,
            e.destino,
            e.descripcion,
            e.estado,
            DATE_FORMAT(e.fecha_envio, '%d/%m/%Y %H:%i') AS fecha
        FROM envios e
        INNER JOIN clientes c 
            ON e.cliente_id = c.id
        LEFT JOIN repartidores r 
            ON e.repartidor_id = r.id
        ORDER BY e.id DESC
        LIMIT 10
    ";

    $resultado = $conexion->query($sql);

    $envios = [];

    if ($resultado) {

        while ($fila = $resultado->fetch_assoc()) {
            $envios[] = $fila;
        }

    }

    echo json_encode([
        "ok" => true,
        "envios" => $envios
    ]);

    exit;
}




if ($accion === "clientes") {

    $sql = "
        SELECT
            id,
            nombre,
            telefono,
            correo,
            direccion,
            DATE_FORMAT(fecha_registro, '%d/%m/%Y %H:%i') AS fecha
        FROM clientes
        ORDER BY id DESC
    ";

    $resultado = $conexion->query($sql);

    $clientes = [];

    if ($resultado) {

        while ($fila = $resultado->fetch_assoc()) {
            $clientes[] = $fila;
        }

    }

    echo json_encode([
        "ok" => true,
        "clientes" => $clientes
    ]);

    exit;
}



if ($accion === "crear_cliente") {

    $nombre = trim($_POST["nombre"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $direccion = trim($_POST["direccion"] ?? "");

    if ($nombre === "") {

        echo json_encode([
            "ok" => false,
            "mensaje" => "El nombre del cliente es obligatorio."
        ]);

        exit;
    }

    $sql = "
        INSERT INTO clientes
        (nombre, telefono, correo, direccion)
        VALUES (?, ?, ?, ?)
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {

        echo json_encode([
            "ok" => false,
            "mensaje" => "Error preparando la consulta: " . $conexion->error
        ]);

        exit;
    }

    $stmt->bind_param(
        "ssss",
        $nombre,
        $telefono,
        $correo,
        $direccion
    );

    if ($stmt->execute()) {

        echo json_encode([
            "ok" => true,
            "mensaje" => "Cliente registrado correctamente.",
            "id" => $stmt->insert_id
        ]);

    } else {

        echo json_encode([
            "ok" => false,
            "mensaje" => "No se pudo registrar el cliente: " . $stmt->error
        ]);
    }

    $stmt->close();

    exit;
}




if ($accion === "repartidores") {

    $sql = "
        SELECT
            id,
            nombre,
            telefono,
            vehiculo,
            placa,
            estado
        FROM repartidores
        ORDER BY nombre ASC
    ";

    $resultado = $conexion->query($sql);

    $repartidores = [];

    if ($resultado) {

        while ($fila = $resultado->fetch_assoc()) {
            $repartidores[] = $fila;
        }

    }

    echo json_encode([
        "ok" => true,
        "repartidores" => $repartidores
    ]);

    exit;
}



if ($accion === "crear_envio") {

    $cliente_id = (int)($_POST["cliente_id"] ?? 0);
    $repartidor_id = (int)($_POST["repartidor_id"] ?? 0);

    $origen = trim($_POST["origen"] ?? "");
    $destino = trim($_POST["destino"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");

    if ($cliente_id <= 0) {

        echo json_encode([
            "ok" => false,
            "mensaje" => "Debes seleccionar un cliente."
        ]);

        exit;
    }

    if ($origen === "") {

        echo json_encode([
            "ok" => false,
            "mensaje" => "El origen es obligatorio."
        ]);

        exit;
    }

    if ($destino === "") {

        echo json_encode([
            "ok" => false,
            "mensaje" => "El destino es obligatorio."
        ]);

        exit;
    }


    

    $numero = 1;

    $consultaCodigo = $conexion->query("
        SELECT codigo
        FROM envios
        WHERE codigo LIKE 'ENV-%'
        ORDER BY id DESC
        LIMIT 1
    ");

    if ($consultaCodigo && $consultaCodigo->num_rows > 0) {

        $ultimo = $consultaCodigo->fetch_assoc();

        $numero = (int)str_replace("ENV-", "", $ultimo["codigo"]);

        $numero++;
    }

    $codigo = "ENV-" . str_pad($numero, 5, "0", STR_PAD_LEFT);


  
    $repartidor = null;

    if ($repartidor_id > 0) {
        $repartidor = $repartidor_id;
    }



    $estado = "Pendiente";


    
    $sql = "
        INSERT INTO envios
        (
            codigo,
            cliente_id,
            repartidor_id,
            origen,
            destino,
            descripcion,
            estado
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {

        echo json_encode([
            "ok" => false,
            "mensaje" => "Error preparando envío: " . $conexion->error
        ]);

        exit;
    }

    $stmt->bind_param(
        "siissss",
        $codigo,
        $cliente_id,
        $repartidor,
        $origen,
        $destino,
        $descripcion,
        $estado
    );

    if ($stmt->execute()) {

        echo json_encode([
            "ok" => true,
            "mensaje" => "Envío registrado correctamente.",
            "codigo" => $codigo,
            "estado" => "Pendiente"
        ]);

    } else {

        echo json_encode([
            "ok" => false,
            "mensaje" => "No se pudo registrar el envío: " . $stmt->error
        ]);
    }

    $stmt->close();

    exit;
}




if ($accion === "reportes") {

    $resumen = $conexion->query("
        SELECT
            COUNT(*) AS total,
            SUM(estado = 'Pendiente') AS pendientes,
            SUM(estado = 'En tránsito') AS transito,
            SUM(estado = 'Entregado') AS entregados
        FROM envios
    ");

    $datosResumen = [
        "total" => 0,
        "pendientes" => 0,
        "transito" => 0,
        "entregados" => 0
    ];

    if ($resumen) {

        $fila = $resumen->fetch_assoc();

        $datosResumen["total"] = (int)($fila["total"] ?? 0);
        $datosResumen["pendientes"] = (int)($fila["pendientes"] ?? 0);
        $datosResumen["transito"] = (int)($fila["transito"] ?? 0);
        $datosResumen["entregados"] = (int)($fila["entregados"] ?? 0);
    }


    $sql = "
        SELECT
            e.codigo,
            c.nombre AS cliente,
            COALESCE(r.nombre, 'Sin asignar') AS repartidor,
            e.origen,
            e.destino,
            e.estado,
            DATE_FORMAT(e.fecha_envio, '%d/%m/%Y %H:%i') AS fecha
        FROM envios e
        INNER JOIN clientes c
            ON e.cliente_id = c.id
        LEFT JOIN repartidores r
            ON e.repartidor_id = r.id
        ORDER BY e.id DESC
    ";

    $resultado = $conexion->query($sql);

    $envios = [];

    if ($resultado) {

        while ($fila = $resultado->fetch_assoc()) {
            $envios[] = $fila;
        }

    }


    echo json_encode([
        "ok" => true,
        "resumen" => $datosResumen,
        "envios" => $envios
    ]);

    exit;
}




echo json_encode([
    "ok" => false,
    "mensaje" => "Acción no reconocida."
]);

?>