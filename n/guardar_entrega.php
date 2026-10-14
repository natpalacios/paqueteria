
<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

function respuesta($ok, $mensaje, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode(
        ['exito' => $ok, 'mensaje' => $mensaje],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respuesta(false, 'Solicitud no permitida.', 405);
}

if (!isset($_SESSION['id'])) {
    respuesta(false, 'Tu sesión terminó. Inicia sesión nuevamente.', 401);
}

$usuarioId = (int)$_SESSION['id'];

$stmt = $conexion->prepare(
    'SELECT repartidor_id FROM usuarios WHERE id = ? LIMIT 1'
);
$stmt->bind_param('i', $usuarioId);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario || empty($usuario['repartidor_id'])) {
    respuesta(false, 'No tienes un repartidor asignado.', 403);
}

$repartidorId = (int)$usuario['repartidor_id'];

$envioId = filter_input(INPUT_POST, 'envio_id', FILTER_VALIDATE_INT);
$codigo = trim($_POST['idEntrega'] ?? '');
$viaje = trim($_POST['viaje'] ?? '');
$fechaHora = trim($_POST['fechaHora'] ?? '');
$presencia = trim($_POST['presenciaCliente'] ?? '');
$receptor = trim($_POST['nombreReceptor'] ?? '');
$cedula = trim($_POST['cedula'] ?? '');
$estadoPaquete = trim($_POST['estadoPaquete'] ?? '');
$motivo = trim($_POST['motivo'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');
$estadoFinal = trim($_POST['estadoFinal'] ?? '');

if (!$envioId || $codigo === '') {
    respuesta(false, 'Falta el código o el ID del envío.', 400);
}

if (!in_array($presencia, [
    'Entregado con éxito',
    'Cliente Ausente'
], true)) {
    respuesta(false, 'Selecciona una opción válida para la presencia del cliente.', 400);
}

if (!in_array($estadoPaquete, [
    'Caja en buen estado',
    'Caja con daño leve',
    'Caja averiada'
], true)) {
    respuesta(false, 'Selecciona un estado válido para el paquete.', 400);
}

if (!in_array($estadoFinal, [
    'Entregado con éxito',
    'Entrega con novedad',
    'No entregado / Incidencia',
    'Reintentar entrega'
], true)) {
    respuesta(false, 'Selecciona un resultado válido.', 400);
}

if (!in_array($motivo, [
    '',
    'Cliente Ausente',
    'Dirección incorrecta',
    'Empaque con daño físico',
    'Producto dañado',
    'Cliente rechazó el pedido',
    'Otro'
], true)) {
    respuesta(false, 'El motivo seleccionado no es válido.', 400);
}

/* Validar fecha recibida desde datetime-local. */
$fecha = DateTime::createFromFormat('!Y-m-d\TH:i', $fechaHora);

if (!$fecha || $fecha->format('Y-m-d\TH:i') !== $fechaHora) {
    respuesta(false, 'La fecha y hora no son válidas.', 400);
}

$fechaSQL = $fecha->format('Y-m-d H:i:s');

/* Comprobar que el envío corresponde a este repartidor. */
$stmt = $conexion->prepare(
    'SELECT id FROM envios
     WHERE id = ? AND codigo = ? AND repartidor_id = ?
     LIMIT 1'
);
$stmt->bind_param('isi', $envioId, $codigo, $repartidorId);
$stmt->execute();
$envioValido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$envioValido) {
    respuesta(false, 'El envío no existe o no está asignado a tu usuario.', 403);
}

/* Crear carpeta para guardar las evidencias. */
$carpeta = __DIR__ . '/uploads/entregas/';

if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true)) {
    respuesta(false, 'No se pudo crear la carpeta de evidencias.', 500);
}

$firmaRuta = null;
$fotoRuta = null;
$archivosGuardados = [];

/* Guardar firma PNG enviada desde el canvas. */
$firma = $_POST['firma'] ?? '';

if ($firma !== '') {
    if (!preg_match('/^data:image\/png;base64,/', $firma)) {
        respuesta(false, 'El formato de la firma no es válido.', 400);
    }

    $contenidoFirma = base64_decode(
        substr($firma, strpos($firma, ',') + 1),
        true
    );

    if ($contenidoFirma === false || strlen($contenidoFirma) > 2 * 1024 * 1024) {
        respuesta(false, 'La firma supera el tamaño permitido.', 400);
    }

    if (strlen($contenidoFirma) > 0) {
        $nombreFirma = 'firma_' . bin2hex(random_bytes(12)) . '.png';
        $rutaCompleta = $carpeta . $nombreFirma;

        if (file_put_contents($rutaCompleta, $contenidoFirma) === false) {
            respuesta(false, 'No se pudo guardar la firma.', 500);
        }

        $archivosGuardados[] = $rutaCompleta;
        $firmaRuta = 'uploads/entregas/' . $nombreFirma;
    }
}

/* Guardar fotografía, si se seleccionó una. */
if (
    isset($_FILES['fotoPaquete']) &&
    $_FILES['fotoPaquete']['error'] !== UPLOAD_ERR_NO_FILE
) {
    $archivo = $_FILES['fotoPaquete'];

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        respuesta(false, 'No se pudo recibir la fotografía.', 400);
    }

    if ($archivo['size'] <= 0 || $archivo['size'] > 5 * 1024 * 1024) {
        respuesta(false, 'La fotografía debe pesar como máximo 5 MB.', 400);
    }

    $infoImagen = @getimagesize($archivo['tmp_name']);

    if ($infoImagen === false) {
        respuesta(false, 'El archivo seleccionado no es una imagen válida.', 400);
    }

    $tiposPermitidos = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp'
    ];

    if (!isset($tiposPermitidos[$infoImagen[2]])) {
        respuesta(false, 'Solo se aceptan imágenes JPG, PNG o WEBP.', 400);
    }

    $nombreFoto = 'evidencia_' . bin2hex(random_bytes(12)) .
        '.' . $tiposPermitidos[$infoImagen[2]];

    $rutaCompleta = $carpeta . $nombreFoto;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        respuesta(false, 'No se pudo guardar la fotografía.', 500);
    }

    $archivosGuardados[] = $rutaCompleta;
    $fotoRuta = 'uploads/entregas/' . $nombreFoto;
}

/* Mantener los estados alineados con los usados en rutad.php. */
switch ($estadoFinal) {
    case 'Entregado con éxito':
        $estadoEnvio = 'Entregado';
        break;

    case 'Entrega con novedad':
    case 'No entregado / Incidencia':
        $estadoEnvio = 'Pendiente';
        break;

    case 'Reintentar entrega':
        $estadoEnvio = 'Pendiente';
        break;

    default:
        respuesta(false, 'Resultado de entrega no válido.', 400);
}

try {
    $conexion->begin_transaction();

    $sql = "INSERT INTO detalle_entregas (
        envio_id, viaje, fecha_hora, presencia_cliente,
        nombre_receptor, cedula, firma_ruta, foto_ruta,
        estado_paquete, motivo, observaciones, estado_final
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param(
        'isssssssssss',
        $envioId,
        $viaje,
        $fechaSQL,
        $presencia,
        $receptor,
        $cedula,
        $firmaRuta,
        $fotoRuta,
        $estadoPaquete,
        $motivo,
        $observaciones,
        $estadoFinal
    );
    $stmt->execute();
    $stmt->close();

    if ($estadoFinal === 'Entregado con éxito') {
        $sqlUpdate = "UPDATE envios
                      SET estado = ?, fecha_entrega = ?
                      WHERE id = ? AND codigo = ? AND repartidor_id = ?";
        $stmt = $conexion->prepare($sqlUpdate);
        $stmt->bind_param(
            'ssisi',
            $estadoEnvio,
            $fechaSQL,
            $envioId,
            $codigo,
            $repartidorId
        );
    } else {
        $sqlUpdate = "UPDATE envios
                      SET estado = ?
                      WHERE id = ? AND codigo = ? AND repartidor_id = ?";
        $stmt = $conexion->prepare($sqlUpdate);
        $stmt->bind_param(
            'sisi',
            $estadoEnvio,
            $envioId,
            $codigo,
            $repartidorId
        );
    }

    $stmt->execute();
    $stmt->close();

    $conexion->commit();

    respuesta(true, 'Comprobante guardado correctamente.');
} catch (Throwable $e) {
    try {
        $conexion->rollback();
    } catch (Throwable $ignorado) {
        // La transacción podría no haberse iniciado.
    }

    foreach ($archivosGuardados as $archivoGuardado) {
        if (is_file($archivoGuardado)) {
            @unlink($archivoGuardado);
        }
    }

    error_log('Error en guardar_entrega.php: ' . $e->getMessage());
    respuesta(false, 'No se pudo guardar el comprobante. Revisa la base de datos.', 500);
}
