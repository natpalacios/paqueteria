<?php

require_once __DIR__ . '/conexion.php';

$envio_id = isset($_GET['envio_id'])
    ? (int) $_GET['envio_id']
    : 0;

if ($envio_id <= 0) {
    die('ID de envío inválido.');
}

$sql = "
    SELECT
        id,
        estado,
        direccion,
        departamento,
        municipio
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
    die('El envío no existe.');
}

$estado = strtolower(trim($envio['estado']));

if ($estado !== 'en transito' && $estado !== 'en tránsito') {
    die('Este envío no está en tránsito.');
}

?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Seguimiento del envío</title>

</head>

<body>

    <h2>Seguimiento del envío #<?= htmlspecialchars($envio_id) ?></h2>

    <p>
        Destino:
        <?= htmlspecialchars($envio['direccion']) ?>
    </p>

    <p id="estadoGPS">
        Iniciando GPS...
    </p>

    <p>
        Velocidad:
        <strong id="velocidad">
            0 km/h
        </strong>
    </p>

    <p>
        Latitud:
        <span id="latitud">—</span>
    </p>

    <p>
        Longitud:
        <span id="longitud">—</span>
    </p>

    <script>

        const ENVIO_ID = <?= $envio_id ?>;

    </script>

    <script src="js/rastreo.js"></script>

</body>

</html>