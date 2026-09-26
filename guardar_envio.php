<?php

require_once "conexion.php";

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "mensaje" => "Método no permitido"
    ]);

    exit;
}



$cliente_id = intval($_POST["cliente_id"] ?? 0);

$repartidor_id = intval($_POST["repartidor_id"] ?? 0);

$descripcion = trim($_POST["descripcion"] ?? "");

$estado = trim($_POST["estado"] ?? "Pendiente");

$fecha_envio = $_POST["fecha_envio"] ?? date("Y-m-d");

$fecha_entrega = !empty($_POST["fecha_entrega"])
    ? $_POST["fecha_entrega"]
    : null;

$peso = floatval($_POST["peso"] ?? 0);

$tipo = trim($_POST["tipo"] ?? "");

$departamento = trim($_POST["departamento"] ?? "");

$municipio = trim($_POST["municipio"] ?? "");

$direccion = trim($_POST["direccion"] ?? "");

$responsable_nombre = trim($_POST["responsable_nombre"] ?? "");

$responsable_codigo = trim($_POST["responsable_codigo"] ?? "");

$responsable_vehiculo = trim($_POST["responsable_vehiculo"] ?? "");

$costo_envio = floatval($_POST["costo_envio"] ?? 0);

$total = floatval($_POST["total"] ?? 0);



if ($cliente_id <= 0) {

    echo json_encode([
        "success" => false,
        "mensaje" => "Debe seleccionar un cliente"
    ]);

    exit;
}

if ($repartidor_id <= 0) {

    echo json_encode([
        "success" => false,
        "mensaje" => "Debe seleccionar un repartidor"
    ]);

    exit;
}

if ($peso <= 0) {

    echo json_encode([
        "success" => false,
        "mensaje" => "El peso debe ser mayor que 0"
    ]);

    exit;
}

if ($municipio === "") {

    echo json_encode([
        "success" => false,
        "mensaje" => "Debe seleccionar un municipio"
    ]);

    exit;
}



$fechaCodigo = date("Ymd");

$sqlUltimo = "
    SELECT codigo
    FROM envios
    WHERE codigo LIKE ?
    ORDER BY id DESC
    LIMIT 1
";

$patron = "ENV-" . $fechaCodigo . "-%";

$stmt = $conexion->prepare($sqlUltimo);

$stmt->bind_param("s", $patron);

$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {

    $fila = $resultado->fetch_assoc();

    $ultimoCodigo = $fila["codigo"];

    $partes = explode("-", $ultimoCodigo);

    $numero = intval(end($partes));

    $numero++;

} else {

    $numero = 1;
}

$codigo = "ENV-" . $fechaCodigo . "-" . str_pad(
    $numero,
    4,
    "0",
    STR_PAD_LEFT
);

$stmt->close();




$sql = "
INSERT INTO envios
(
    codigo,
    cliente_id,
    repartidor_id,
    descripcion,
    estado,
    fecha_envio,
    fecha_entrega,
    peso,
    tipo,
    departamento,
    municipio,
    direccion,
    responsable_nombre,
    responsable_codigo,
    responsable_vehiculo,
    costo_envio,
    total
)
VALUES
(
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
)
";

$stmt = $conexion->prepare($sql);

$stmt->bind_param(
    "siissssdssssssssd",
    $codigo,
    $cliente_id,
    $repartidor_id,
    $descripcion,
    $estado,
    $fecha_envio,
    $fecha_entrega,
    $peso,
    $tipo,
    $departamento,
    $municipio,
    $direccion,
    $responsable_nombre,
    $responsable_codigo,
    $responsable_vehiculo,
    $costo_envio,
    $total
);


if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "mensaje" => "Envío registrado correctamente",
        "codigo" => $codigo,
        "id" => $conexion->insert_id
    ]);

} else {

    echo json_encode([
        "success" => false,
        "mensaje" => "Error al guardar el envío: " . $stmt->error
    ]);
}


$stmt->close();

$conexion->close();

?>

