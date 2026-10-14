
<?php
session_start();
require_once __DIR__ . '/../conexion.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../login.php');
    exit;
}

$codigo = trim($_GET['codigo'] ?? '');

if ($codigo === '') {
    http_response_code(400);
    exit('No se recibió el código del envío. Regresa a Mis entregas.');
}

/* Verificar usuario y repartidor asignado. */
$sqlUsuario = "SELECT repartidor_id FROM usuarios WHERE id = ? LIMIT 1";
$stmt = $conexion->prepare($sqlUsuario);
$stmt->bind_param('i', $_SESSION['id']);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario || empty($usuario['repartidor_id'])) {
    http_response_code(403);
    exit('Tu usuario no tiene un repartidor asignado.');
}

$repartidorId = (int)$usuario['repartidor_id'];

/* Buscar el envío por su código y comprobar que pertenece al repartidor. */
$sql = "SELECT
            e.id,
            e.codigo,
            e.estado,
            e.fecha_envio,
            e.fecha_entrega,
            e.departamento,
            e.municipio,
            e.direccion,
            e.descripcion,
            e.responsable_nombre,
            e.responsable_codigo,
            e.responsable_vehiculo,
            c.nombre AS cliente_nombre
        FROM envios e
        LEFT JOIN clientes c ON c.id = e.cliente_id
        WHERE e.codigo = ? AND e.repartidor_id = ?
        LIMIT 1";

$stmt = $conexion->prepare($sql);
$stmt->bind_param('si', $codigo, $repartidorId);
$stmt->execute();
$envio = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$envio) {
    http_response_code(404);
    exit('No se encontró el envío o no está asignado a tu usuario.');
}

function h($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

$destino = trim(
    ($envio['municipio'] ?? '') .
    (!empty($envio['departamento']) ? ', ' . $envio['departamento'] : '')
);

$fechaInicial = !empty($envio['fecha_entrega'])
    ? date('Y-m-d\TH:i', strtotime($envio['fecha_entrega']))
    : date('Y-m-d\TH:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupo S.A Logistic - Detalle de entrega</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<main class="contenedor">
    <section class="modal">

        <h2>DETALLE DE ENTREGA</h2>
        <p>Prueba de entrega e incidencias</p>

        <h3 class="viaje">
            Detalle de entrega – Viaje
            <input type="text" id="textoViaje" value="VJ-<?= h($envio['codigo']) ?>">
        </h3>

        <div id="mensaje" role="status" aria-live="polite"></div>

        <form id="entregaForm" enctype="multipart/form-data">

            <input type="hidden" name="envio_id" value="<?= (int)$envio['id'] ?>">

            <label for="idEntrega">Código del envío</label>
            <input type="text" id="idEntrega" name="idEntrega"
                   value="<?= h($envio['codigo']) ?>" readonly required>

            <label for="fechaHora">Fecha y hora de entrega</label>
            <input type="datetime-local" id="fechaHora" name="fechaHora"
                   value="<?= h($fechaInicial) ?>" required>

            <label for="destino">Destino</label>
            <input type="text" id="destino" name="destino"
                   value="<?= h($destino) ?>" readonly>

            <label for="direccion">Dirección de entrega</label>
            <input type="text" id="direccion" name="direccion"
                   value="<?= h($envio['direccion']) ?>" readonly>

            <label for="paquete">Paquete</label>
            <input type="text" id="paquete" name="paquete"
                   value="<?= h($envio['codigo']) ?>" readonly>

            <label for="conductor">Repartidor</label>
            <input type="text" id="conductor" name="conductor"
                   value="<?= h($envio['responsable_nombre']) ?>" readonly>

            <hr>

            <h3>Confirmación del cliente</h3>

            <label>
                <input type="radio" name="presenciaCliente"
                       value="Entregado con éxito" checked>
                Entregado con éxito
            </label>

            <label>
                <input type="radio" name="presenciaCliente"
                       value="Cliente Ausente">
                Cliente ausente
            </label>

            <label for="nombreReceptor">Nombre de quien recibe</label>
            <input type="text" id="nombreReceptor" name="nombreReceptor">

            <label for="cedula">Cédula de quien recibe</label>
            <input type="text" id="cedula" name="cedula">

            <h3>Firma del receptor</h3>
            <canvas id="firmaCanvas" width="500" height="150"
                    style="max-width:100%; border:1px solid #aaa; touch-action:none"></canvas>
            <button type="button" id="borrarFirma">Borrar firma</button>

            <hr>

            <h3>Evidencia fotográfica</h3>
            <label for="fotoPaquete">Fotografía del paquete (máximo 5 MB)</label>
            <input type="file" id="fotoPaquete" name="fotoPaquete"
                   accept="image/jpeg,image/png,image/webp">
            <img id="previewImagen" alt="Vista previa de la fotografía"
                 style="display:none; max-width:100%; max-height:250px">

            <hr>

            <h3>Estado del paquete</h3>

            <label>
                <input type="radio" name="estadoPaquete"
                       value="Caja en buen estado" checked>
                Caja en buen estado
            </label>

            <label>
                <input type="radio" name="estadoPaquete"
                       value="Caja con daño leve">
                Caja con daño leve
            </label>

            <label>
                <input type="radio" name="estadoPaquete"
                       value="Caja averiada">
                Caja averiada
            </label>

            <label for="motivo">Motivo o incidencia</label>
            <select id="motivo" name="motivo">
                <option value="">Sin incidencia</option>
                <option value="Cliente Ausente">Cliente ausente</option>
                <option value="Dirección incorrecta">Dirección incorrecta</option>
                <option value="Empaque con daño físico">Empaque con daño físico</option>
                <option value="Producto dañado">Producto dañado</option>
                <option value="Cliente rechazó el pedido">Cliente rechazó el pedido</option>
                <option value="Otro">Otro</option>
            </select>

            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" rows="4"></textarea>

            <label for="estadoFinal">Resultado de la entrega</label>
            <select id="estadoFinal" name="estadoFinal" required>
                <option value="Entregado con éxito">Entregado con éxito</option>
                <option value="Entrega con novedad">Entrega con novedad</option>
                <option value="No entregado / Incidencia">No entregado / Incidencia</option>
                <option value="Reintentar entrega">Reintentar entrega</option>
            </select>

            <div class="acciones">
                <button type="reset">Limpiar formulario</button>
                <button type="submit" id="guardarEntrega">Guardar comprobante</button>
            </div>

        </form>
    </section>
</main>

<script src="script.js"></script>
</body>
</html>
