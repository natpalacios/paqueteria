<?php
session_start();

require_once "conexion.php";



if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$usuarioId = (int) $_SESSION['id'];



$sqlUsuario = "
    SELECT
        u.id,
        u.nombre,
        u.usuario,
        u.rol,
        u.repartidor_id,
        r.nombre AS nombre_repartidor,
        r.codigo AS codigo_repartidor,
        r.vehiculo AS vehiculo_repartidor
    FROM usuarios u
    LEFT JOIN repartidores r
        ON r.id = u.repartidor_id
    WHERE u.id = ?
    LIMIT 1
";

$stmtUsuario = $conexion->prepare($sqlUsuario);
$stmtUsuario->bind_param("i", $usuarioId);
$stmtUsuario->execute();

$resultUsuario = $stmtUsuario->get_result();
$usuario = $resultUsuario->fetch_assoc();

if (!$usuario) {
    die("No se encontró el usuario.");
}

$repartidorId = (int) ($usuario['repartidor_id'] ?? 0);

if ($repartidorId <= 0) {
    die("Este usuario no tiene un repartidor asignado.");
}


if (
    isset($_GET['accion']) &&
    $_GET['accion'] === 'iniciar_entrega'
) {

    header('Content-Type: application/json; charset=utf-8');

    $envioId = (int) ($_POST['id'] ?? 0);

    if ($envioId <= 0) {
        echo json_encode([
            "ok" => false,
            "mensaje" => "ID de envío inválido."
        ]);
        exit;
    }

    $sql = "
        UPDATE envios
        SET estado = 'En tránsito'
        WHERE id = ?
          AND repartidor_id = ?
          AND estado = 'Pendiente'
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param(
        "ii",
        $envioId,
        $repartidorId
    );

    if (!$stmt->execute()) {
        echo json_encode([
            "ok" => false,
            "mensaje" => "No se pudo actualizar el envío."
        ]);
        exit;
    }

    if ($stmt->affected_rows <= 0) {

        echo json_encode([
            "ok" => false,
            "mensaje" => "El envío no está pendiente o no pertenece a este repartidor."
        ]);

        exit;
    }

    echo json_encode([
        "ok" => true,
        "mensaje" => "Entrega iniciada correctamente.",
        "estado" => "En tránsito"
    ]);

    exit;
}

$sqlEnvios = "
    SELECT
        e.id,
        e.codigo,
        e.cliente_id,
        e.repartidor_id,
        e.descripcion,
        e.estado,
        e.fecha_envio,
        e.fecha_entrega,
        e.peso,
        e.tipo,
        e.departamento,
        e.municipio,
        e.direccion,
        e.responsable_nombre,
        e.responsable_codigo,
        e.responsable_vehiculo,
        e.costo_envio,

        c.nombre AS cliente_nombre,
        c.telefono AS cliente_telefono,
        c.correo AS cliente_correo,

        r.nombre AS repartidor_nombre,
        r.codigo AS repartidor_codigo,
        r.vehiculo AS repartidor_vehiculo

    FROM envios e

    LEFT JOIN clientes c
        ON c.id = e.cliente_id

    LEFT JOIN repartidores r
        ON r.id = e.repartidor_id

    WHERE e.repartidor_id = ?

    ORDER BY e.id ASC
";

$stmtEnvios = $conexion->prepare($sqlEnvios);
$stmtEnvios->bind_param("i", $repartidorId);
$stmtEnvios->execute();

$resultEnvios = $stmtEnvios->get_result();

$envios = [];



while ($envio = $resultEnvios->fetch_assoc()) {

   

    $latitud = null;
    $longitud = null;
    $velocidad = null;
    $precisionGps = null;
    $fechaGps = null;

    $sqlGps = "
        SELECT
            latitud,
            longitud,
            velocidad,
            precision_gps,
            fecha_hora
        FROM gps_posiciones
        WHERE envio_id = ?
        ORDER BY fecha_hora DESC, id DESC
        LIMIT 1
    ";

    $stmtGps = $conexion->prepare($sqlGps);
    $stmtGps->bind_param("i", $envio['id']);
    $stmtGps->execute();

    $resultadoGps = $stmtGps->get_result();
    $gps = $resultadoGps->fetch_assoc();

    if ($gps) {
        $latitud = (float) $gps['latitud'];
        $longitud = (float) $gps['longitud'];
        $velocidad = $gps['velocidad'] !== null
            ? (float) $gps['velocidad']
            : null;
        $precisionGps = $gps['precision_gps'] !== null
            ? (float) $gps['precision_gps']
            : null;
        $fechaGps = $gps['fecha_hora'];
    }

  
    $partesDestino = [];

    if (!empty($envio['direccion'])) {
        $partesDestino[] = trim($envio['direccion']);
    }

    if (!empty($envio['municipio'])) {
        $partesDestino[] = trim($envio['municipio']);
    }

    if (!empty($envio['departamento'])) {
        $partesDestino[] = trim($envio['departamento']);
    }

    $partesDestino[] = "Nicaragua";

    $destino = implode(", ", $partesDestino);

  

    $estadoOriginal = trim($envio['estado'] ?? '');

    $estadoFiltro = 'pendiente';

    if (mb_strtolower($estadoOriginal) === 'en tránsito') {
        $estadoFiltro = 'en-ruta';
    } elseif (mb_strtolower($estadoOriginal) === 'entregado') {
        $estadoFiltro = 'entregado';
    } elseif (mb_strtolower($estadoOriginal) === 'cancelado') {
        $estadoFiltro = 'cancelado';
    }

    $envio['estadoFiltro'] = $estadoFiltro;
    $envio['destino'] = $destino;

    $envio['latitud'] = $latitud;
    $envio['longitud'] = $longitud;
    $envio['velocidad'] = $velocidad;
    $envio['precision_gps'] = $precisionGps;
    $envio['fecha_gps'] = $fechaGps;

    $envios[] = $envio;
}



$totalEnvios = count($envios);

$pendientes = 0;
$enTransito = 0;
$entregados = 0;

foreach ($envios as $e) {

    $estado = mb_strtolower(
        trim($e['estado'] ?? '')
    );

    if ($estado === 'pendiente') {
        $pendientes++;
    }

    if ($estado === 'en tránsito') {
        $enTransito++;
    }

    if ($estado === 'entregado') {
        $entregados++;
    }
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

    <title>Ruta del Transportista - Grupo S.A Logistic</title>

    
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

  
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >
    <link
    rel="stylesheet"
    href="css/paquete.css"
     >

     <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      ></script>
    <style>

        * {
            box-sizing: border-box;
        }

        body {

          



            margin: 0;
            background: #f4f7f9;
            font-family: Arial, Helvetica, sans-serif;
            color: #24323d;
        }

        .topbar {
            height: 70px;
            background: white;
            border-bottom: 1px solid #e3e8ec;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: bold;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #0d6efd;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .user-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #e8f1ff;
            color: #0d6efd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .user-name {
            font-weight: bold;
        }

        .user-role {
            font-size: 12px;
            color: #777;
        }

        .page {
            padding: 25px;
        }

        .page-title {
            margin-bottom: 20px;
        }

        .page-title h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .page-title p {
            margin: 5px 0 0;
            color: #777;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat {
            background: white;
            border-radius: 12px;
            padding: 18px;
            border: 1px solid #e4e8ec;
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
        }

        .stat-label {
            color: #777;
            font-size: 14px;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 430px 1fr;
            gap: 20px;
            align-items: start;
        }

        .deliveries {
            background: white;
            border-radius: 14px;
            border: 1px solid #e1e6ea;
            padding: 18px;
        }

        .deliveries-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .deliveries-title h2 {
            font-size: 19px;
            margin: 0;
        }

        .delivery-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 610px;
            overflow-y: auto;
        }

        .delivery-card {
            border: 1px solid #e0e5e9;
            border-radius: 12px;
            padding: 15px;
            background: #fff;
        }

        .delivery-card:hover {
            border-color: #b9cbe0;
        }

        .delivery-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
        }

        .codigo {
            font-weight: bold;
            font-size: 16px;
        }

        .cliente {
            margin-top: 5px;
            color: #555;
            font-size: 14px;
        }

        .direccion {
            margin-top: 12px;
            background: #f6f8fa;
            border-radius: 8px;
            padding: 10px;
            font-size: 13px;
            color: #555;
        }

        .estado {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .estado-pendiente {
            background: #fff3cd;
            color: #856404;
        }

        .estado-ruta {
            background: #d1e7dd;
            color: #0f5132;
        }

        .estado-entregado {
            background: #cff4fc;
            color: #055160;
        }

        .estado-cancelado {
            background: #f8d7da;
            color: #842029;
        }

        .map-container {
            background: white;
            border-radius: 14px;
            border: 1px solid #e1e6ea;
            overflow: hidden;
        }

        .map-header {
            padding: 17px 20px;
            border-bottom: 1px solid #e5e8eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .map-header h2 {
            font-size: 19px;
            margin: 0;
        }

        #mapaRuta {
            width: 100%;
            height: 600px;
        }

        .map-info {
            padding: 15px 20px;
            background: white;
            border-top: 1px solid #e5e8eb;
        }

        #textoMapa {
            color: #666;
            font-size: 14px;
        }

        #infoUbicacion {
            display: none;
            margin-top: 12px;
            padding: 12px;
            border-radius: 9px;
            background: #f5f8fb;
        }

        .empty {
            padding: 30px 10px;
            text-align: center;
            color: #777;
        }

        .hidden {
            display: none !important;
        }

        @media (max-width: 1000px) {

            .main-grid {
                grid-template-columns: 1fr;
            }

            #mapaRuta {
                height: 500px;
            }

        }

        @media (max-width: 650px) {

            .stats {
                grid-template-columns: 1fr;
            }

            .page {
                padding: 15px;
            }

            .topbar {
                padding: 0 15px;
            }

            .user-box > div:last-child {
                display: none;
            }

        }

    </style>

</head>

<body>
    
<!-- ============================================================
     MENÚ LATERAL
     ============================================================ -->

<div
    class="offcanvas offcanvas-start"
    tabindex="-1"
    id="menuTransportista"
>

    <div class="offcanvas-header">

        <div>

            <h5 class="offcanvas-title">
                Grupo S.A Logistics
            </h5>

            <div class="company-subtitle">
                Paquetería y Envíos
            </div>

        </div>

        <button
            type="button"
            class="btn-close btn-close-white"
            data-bs-dismiss="offcanvas"
        ></button>

    </div>


    <div class="offcanvas-body">

        <div class="menu-container">

            <a
                href="rutad.php"
                class="menu-link"
            >

                <i class="bi bi-geo-alt-fill"></i>

                <span>
                    Mi ruta
                </span>

            </a>


            <a
                href="paquetes.php"
                class="menu-link"
            >

                <i class="bi bi-box-seam-fill"></i>

                <span>
                    Mis paquetes
                </span>

            </a>


            <a
                href="Ayuda y soporte.php"
                class="menu-link"
            >

                <i class="bi bi-person-fill"></i>

                <span>
                    Ayuda/Soporte
                </span>

            </a>


            <a
                href="perfil.php"
                class="menu-link"
            >

                <i class="bi bi-person-fill"></i>

                <span>
                    Mi perfil
                </span>

            </a>


            <a
                href="logout.php"
                class="menu-link"
            >

                <i class="bi bi-box-arrow-right"></i>

                <span>
                    Cerrar sesión
                </span>

            </a>

        </div>

    </div>

</div>

<header class="topbar">

    <div class="d-flex align-items-center gap-3">

        <!-- BOTÓN MENÚ DE TRES LÍNEAS -->
        <button
            class="menu-btn"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#menuTransportista"
            aria-controls="menuTransportista"
        >
            <i class="bi bi-list"></i>
        </button>

        <div class="brand">
            <div class="brand-icon">
                <i class="bi bi-truck"></i>
            </div>
            <span>Grupo S.A Logistic</span>
        </div>

    </div>

    <div class="user-box">
        <div class="user-icon">
            <i class="bi bi-person-fill"></i>
        </div>
        <div>
            <div class="user-name">
                <?= htmlspecialchars($usuario['nombre']) ?>
            </div>

            <div class="user-role">
                <?= htmlspecialchars($usuario['rol']) ?>

                <?php if (!empty($usuario['nombre_repartidor'])): ?>
                    · <?= htmlspecialchars($usuario['nombre_repartidor']) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</header>



<main class="page">

    <div class="page-title">

        <h1>
            Ruta de entregas
        </h1>

        <p>
            Consulta y administra las entregas asignadas a ti.
        </p>

    </div>


    <div class="stats">

        <div class="stat">

            <div class="stat-number">
                <?= $totalEnvios ?>
            </div>

            <div class="stat-label">
                Envíos asignados
            </div>

        </div>

        <div class="stat">

            <div class="stat-number">
                <?= $pendientes ?>
            </div>

            <div class="stat-label">
                Pendientes
            </div>

        </div>

        <div class="stat">

            <div class="stat-number">
                <?= $enTransito ?>
            </div>

            <div class="stat-label">
                En tránsito
            </div>

        </div>

    </div>


    <div class="main-grid">

     
        <section class="deliveries">

            <div class="deliveries-title">

                <h2>
                    Mis entregas
                </h2>

                <span class="badge bg-primary">
                    <?= $totalEnvios ?>
                </span>

            </div>


            <div
                id="listaEntregas"
                class="delivery-list"
            >

                <?php if (empty($envios)): ?>

                    <div class="empty">

                        <i
                            class="bi bi-box-seam"
                            style="font-size: 35px;"
                        ></i>

                        <p class="mt-2">
                            No tienes envíos asignados.
                        </p>

                    </div>

                <?php else: ?>

                    <?php foreach ($envios as $envio): ?>

                        <div
                            class="delivery-card"
                            id="entrega-<?= (int)$envio['id'] ?>"
                            data-status="<?= htmlspecialchars($envio['estadoFiltro']) ?>"
                            data-codigo="<?= htmlspecialchars($envio['codigo']) ?>"
                        >

                            <div class="delivery-header">

                                <div>

                                    <div class="codigo">

                                        <?= htmlspecialchars($envio['codigo']) ?>

                                    </div>

                                    <div class="cliente">

                                        <i class="bi bi-person"></i>

                                        <?= htmlspecialchars(
                                            $envio['cliente_nombre'] ?? 'Cliente'
                                        ) ?>

                                    </div>

                                </div>


                                <?php

                                $claseEstado = 'estado-pendiente';

                                if ($envio['estadoFiltro'] === 'en-ruta') {
                                    $claseEstado = 'estado-ruta';
                                }

                                if ($envio['estadoFiltro'] === 'entregado') {
                                    $claseEstado = 'estado-entregado';
                                }

                                if ($envio['estadoFiltro'] === 'cancelado') {
                                    $claseEstado = 'estado-cancelado';
                                }

                                ?>

                                <span
                                    id="estado-<?= (int)$envio['id'] ?>"
                                    class="estado <?= $claseEstado ?>"
                                >

                                    <?= htmlspecialchars($envio['estado']) ?>

                                </span>

                            </div>


                            <div class="direccion">

                                <i class="bi bi-geo-alt-fill"></i>

                                <?= htmlspecialchars($envio['destino']) ?>

                            </div>


                            <?php if (!empty($envio['descripcion'])): ?>

                                <div class="mt-2 small text-muted">

                                    <?= htmlspecialchars($envio['descripcion']) ?>

                                </div>

                            <?php endif; ?>


                            <div
                                class="d-flex gap-2 mt-3"
                                id="botones-<?= (int)$envio['id'] ?>"
                            >

                                <?php if ($envio['estadoFiltro'] === 'pendiente'): ?>

                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm"
                                        id="btn-iniciar-<?= (int)$envio['id'] ?>"
                                        onclick="iniciarEntrega(<?= (int)$envio['id'] ?>)"
                                    >

                                        <i class="bi bi-play-fill"></i>

                                        Iniciar entrega

                                    </button>

                                <?php elseif ($envio['estadoFiltro'] === 'en-ruta'): ?>

                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm"
                                        onclick="mostrarEnMapa(<?= (int)$envio['id'] ?>)"
                                    >

                                        <i class="bi bi-geo-alt-fill"></i>

                                        Ver ubicación

                                    </button>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </section>


        <section class="map-container">

            <div class="map-header">

                <h2>

                    <i class="bi bi-map"></i>

                    Mapa de ruta

                </h2>

                <span class="badge bg-light text-dark">
                    OpenStreetMap
                </span>

            </div>


            <div id="mapaRuta"></div>


            <div class="map-info">

                <div id="textoMapa">

                    Selecciona una entrega para visualizar su ubicación.

                </div>


                <div id="infoUbicacion">

                    <div>
                        <strong id="infoCodigo"></strong>
                    </div>

                    <div id="infoCliente"></div>

                    <div id="infoDireccion"></div>

                </div>

            </div>

        </section>

    </div>

</main>


<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>



<script>

    const envios =
        <?= json_encode(
            $envios,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_NUMERIC_CHECK
        ) ?>;

</script>



<script src="js/transportista.js"></script>


</body>
</html>