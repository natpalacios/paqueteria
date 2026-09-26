<?php

require_once "conexion.php";
require_once "config.php";



$sql = "
    SELECT
        id,
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
    FROM envios
    ORDER BY id DESC
";

$resultado = $conexion->query($sql);

if (!$resultado) {
    die("Error al consultar envios: " . $conexion->error);
}


$envios = [];


while ($fila = $resultado->fetch_assoc()) {

    $estadoOriginal = trim((string)$fila['estado']);

    $estado = strtolower($estadoOriginal);


    if (
        strpos($estado, 'entreg') !== false ||
        strpos($estado, 'finaliz') !== false ||
        strpos($estado, 'complet') !== false
    ) {

        $estadoFiltro = "entregado";

    } elseif (
        strpos($estado, 'tránsito') !== false ||
        strpos($estado, 'transito') !== false ||
        strpos($estado, 'ruta') !== false ||
        strpos($estado, 'enviado') !== false
    ) {

        $estadoFiltro = "en-ruta";

    } elseif (
        strpos($estado, 'cancel') !== false
    ) {

        $estadoFiltro = "cancelado";

    } else {

        $estadoFiltro = "pendiente";
    }

    $municipio =
        trim((string)$fila['municipio']);

    $departamento =
        trim((string)$fila['departamento']);

    $direccion =
        trim((string)$fila['direccion']);


    $destino = "";

    if ($direccion !== "") {

        $destino .= $direccion . ", ";

    }

    $destino .=
        $municipio . ", " .
        $departamento .
        ", Nicaragua";


    $envios[] = [

        'id' => (int)$fila['id'],

        'codigo' => $fila['codigo'],

        'estado' => $estadoOriginal,

        'estadoFiltro' => $estadoFiltro,

        'cliente_id' => $fila['cliente_id'],

        'repartidor_id' => $fila['repartidor_id'],

        'descripcion' => $fila['descripcion'],

        'fecha_envio' => $fila['fecha_envio'],

        'fecha_entrega' => $fila['fecha_entrega'],

        'peso' => $fila['peso'],

        'tipo' => $fila['tipo'],

        'departamento' => $departamento,

        'municipio' => $municipio,

        'direccion' => $direccion,

        'destino' => $destino,

        'responsable_nombre' =>
            $fila['responsable_nombre'],

        'responsable_codigo' =>
            $fila['responsable_codigo'],

        'responsable_vehiculo' =>
            $fila['responsable_vehiculo'],

        'costo_envio' =>
            $fila['costo_envio'],

        'total' =>
            $fila['total']

    ];
}



$totalEnvios = count($envios);

$pendientes = 0;
$enRuta = 0;
$entregados = 0;
$cancelados = 0;


foreach ($envios as $envio) {

    switch ($envio['estadoFiltro']) {

        case 'en-ruta':
            $enRuta++;
            break;

        case 'entregado':
            $entregados++;
            break;

        case 'cancelado':
            $cancelados++;
            break;

        default:
            $pendientes++;
            break;
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

    <title>Rastreo de envíos</title>




    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >


  

    <link
        rel="stylesheet"
        href="css/monitoreo.css"
    >

</head>


<body>



<header class="topbar">


    <div class="topbar-left">



        <button
            type="button"
            id="menuButton"
            class="menu-button"
            aria-label="Abrir menú"
        >

            <span></span>
            <span></span>
            <span></span>

        </button>


        <div class="brand">

            <strong>Paquetería</strong>

            <span>Supervisor</span>

        </div>

    </div>


    <div class="topbar-right">



        <div class="notification-wrapper">


            <button
                type="button"
                id="notificationButton"
                class="notification-button"
                aria-label="Notificaciones"
            >

                <span class="bell-icon">🔔</span>

                <span
                    id="notificationBadge"
                    class="notification-badge"
                >
                    0
                </span>

            </button>


            <div
                id="notificationPanel"
                class="notification-panel"
            >

                <div class="notification-header">

                    <strong>
                        Notificaciones
                    </strong>

                    <button
                        type="button"
                        id="markNotificationsRead"
                    >
                        Marcar vistas
                    </button>

                </div>


                <div
                    id="notificationList"
                    class="notification-list"
                >

                    <div class="notification-empty">

                        No hay notificaciones nuevas.

                    </div>

                </div>

            </div>

        </div>

    </div>

</header>



<aside
    id="sideMenu"
    class="side-menu"
>


    <div class="side-menu-header">

        <div>

            <strong>
                Menú
            </strong>

            <small>
                Panel supervisor
            </small>

        </div>


        <button
            type="button"
            id="closeMenu"
            class="close-menu"
        >
            ×
        </button>

    </div>


    <nav class="side-navigation">


        <a
            href="supervisor.php"
            class="menu-option"
        >

            <span>🏠</span>

            <div>

                <strong>
                    Inicio
                </strong>

                <small>
                    Panel principal
                </small>

            </div>

        </a>


        <a
            href="monitoreo.php"
            class="menu-option active"
        >

            <span>📍</span>

            <div>

                <strong>
                    Rastreo
                </strong>

                <small>
                    Monitorear envíos
                </small>

            </div>

        </a>


       

    </nav>

</aside>



<div
    id="menuOverlay"
    class="menu-overlay"
></div>



<main class="monitor-container">



    <section class="stats-grid">


        <div class="stat-card">

            <div class="stat-icon">
                🚚
            </div>

            <div>

                <span class="stat-title">
                    En ruta
                </span>

                <strong>
                    <?= $enRuta ?>
                </strong>

                <small>
                    unidades
                </small>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ⏸
            </div>

            <div>

                <span class="stat-title">
                    Pendientes
                </span>

                <strong>
                    <?= $pendientes ?>
                </strong>

                <small>
                    unidades
                </small>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ✓
            </div>

            <div>

                <span class="stat-title">
                    Entregados
                </span>

                <strong>
                    <?= $entregados ?>
                </strong>

                <small>
                    unidades
                </small>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ⚡
            </div>

            <div>

                <span class="stat-title">
                    Velocidad promedio
                </span>

                <strong>
                    —
                </strong>

                <small>
                    GPS no disponible
                </small>

            </div>

        </div>


    </section>




    <section class="monitor-grid">



        <div class="map-panel">


            <div class="panel-header">


                <div>

                    <h2>
                        Rastreo en mapa
                    </h2>

                    <p id="routeInfo">

                        Seleccione un envío para
                        visualizar su ruta.

                    </p>

                </div>


                <button
                    type="button"
                    id="clearRoute"
                    class="btn-secondary"
                >

                    Limpiar ruta

                </button>


            </div>


            <div id="map"></div>


        </div>



        <div class="fleet-panel">


            <div class="fleet-header">

                <div>

                    <h2>
                        Envíos activos
                    </h2>

                   

                </div>


                <span id="vehicleCount">

                    <?= $totalEnvios ?>

                </span>

            </div>

            <div class="tabs">


                <button
                    type="button"
                    class="tab active"
                    data-filter="todos"
                >
                    Todos
                </button>


                <button
                    type="button"
                    class="tab"
                    data-filter="en-ruta"
                >
                    En ruta
                </button>


                <button
                    type="button"
                    class="tab"
                    data-filter="pendiente"
                >
                    Pendientes
                </button>


                <button
                    type="button"
                    class="tab"
                    data-filter="entregado"
                >
                    Entregados
                </button>


            </div>


            <div id="vehicleList">


                <?php if (empty($envios)): ?>


                    <div class="empty-message visible">

                        <h3>
                            No hay envíos
                        </h3>

                        <p>
                            No existen registros
                            en la tabla envios.
                        </p>

                    </div>


                <?php else: ?>


                    <?php foreach ($envios as $envio): ?>


                        <article
                            class="vehicle-card"
                            data-status="<?= htmlspecialchars($envio['estadoFiltro']) ?>"
                            data-codigo="<?= htmlspecialchars($envio['codigo']) ?>"
                        >


                            <div class="vehicle-card-header">


                                <div>

                                    <span class="vehicle-code">

                                        <?= htmlspecialchars(
                                            $envio['codigo']
                                        ) ?>

                                    </span>


                                    <span
                                        class="status-badge status-<?= htmlspecialchars(
                                            $envio['estadoFiltro']
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $envio['estado']
                                        ) ?>

                                    </span>

                                </div>


                            </div>



                            <div class="vehicle-info">


                                <div class="info-row">

                                    <span>
                                        Responsable
                                    </span>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $envio['responsable_nombre']
                                            ?: 'No asignado'
                                        ) ?>

                                    </strong>

                                </div>


                                <div class="info-row">

                                    <span>
                                        Vehículo
                                    </span>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $envio['responsable_vehiculo']
                                            ?: 'No asignado'
                                        ) ?>

                                    </strong>

                                </div>


                                <div class="info-row">

                                    <span>
                                        Destino
                                    </span>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $envio['municipio']
                                        ) ?>,
                                        <?= htmlspecialchars(
                                            $envio['departamento']
                                        ) ?>

                                    </strong>

                                </div>


                                <?php if (
                                    $envio['direccion'] !== ''
                                ): ?>


                                    <div class="info-row">

                                        <span>
                                            Dirección
                                        </span>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $envio['direccion']
                                            ) ?>

                                        </strong>

                                    </div>


                                <?php endif; ?>


                                <div class="info-row">

                                    <span>
                                        Fecha
                                    </span>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $envio['fecha_envio']
                                        ) ?>

                                    </strong>

                                </div>


                            </div>



                            <div class="card-actions">


                                <button
                                    type="button"
                                    class="locate-btn"
                                    data-codigo="<?= htmlspecialchars(
                                        $envio['codigo']
                                    ) ?>"
                                >

                                    📍 Ver ruta

                                </button>


                            </div>


                        </article>


                    <?php endforeach; ?>


                <?php endif; ?>


            </div>


            <div
                id="emptyMessage"
                class="empty-message"
            >

                <h3>
                    No hay resultados
                </h3>

                <p>
                    No existen envíos con este filtro.
                </p>

            </div>


        </div>


    </section>


</main>



<script>

  

    const envios =
        <?= json_encode(
            $envios,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        ) ?>;

</script>


<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>


<script src="js/monitoreo.js"></script>


</body>

</html>