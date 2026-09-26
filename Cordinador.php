<?php

session_start();

require_once "conexion.php";

/* =========================================================
   VERIFICAR SESIÓN
========================================================= */
if (!isset($_SESSION["id"])) {
    header("Location: index.php");
    exit;
}

$nombre = $_SESSION["nombre"] ?? "Coordinador";
$usuario = $_SESSION["usuario"] ?? "";
$rol = $_SESSION["rol"] ?? "Coordinador";

$mensaje = "";
$error = "";

/* =========================================================
   CERRAR SESIÓN
========================================================= */
if (isset($_GET["logout"])) {
    session_unset();
    session_destroy();

    header("Location: index.php");
    exit;
}

/* =========================================================
   MENSAJE CUANDO SE CREA UN ENVÍO
   agregar_envio.php debe redirigir así:
   Cordinador.php?envio=creado&codigo=ENV-00001
========================================================= */
if (isset($_GET["envio"]) && $_GET["envio"] === "creado") {

    $codigoCreado = $_GET["codigo"] ?? "";

    if ($codigoCreado !== "") {
        $mensaje = "El envío " . htmlspecialchars($codigoCreado) . " fue registrado correctamente.";
    } else {
        $mensaje = "El envío fue registrado correctamente.";
    }
}

/* =========================================================
   AGREGAR CLIENTE
========================================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accion"])) {

    $accion = $_POST["accion"];

    if ($accion === "agregar_cliente") {

        $nombreCliente = trim($_POST["nombre"] ?? "");
        $telefono = trim($_POST["telefono"] ?? "");
        $correo = trim($_POST["correo"] ?? "");
        $direccionCliente = trim($_POST["direccion"] ?? "");

        if ($nombreCliente === "") {

            $error = "El nombre del cliente es obligatorio.";

        } else {

            $sql = "INSERT INTO clientes 
                    (nombre, telefono, correo, direccion)
                    VALUES (?, ?, ?, ?)";

            $stmt = $conexion->prepare($sql);

            if ($stmt) {

                $stmt->bind_param(
                    "ssss",
                    $nombreCliente,
                    $telefono,
                    $correo,
                    $direccionCliente
                );

                if ($stmt->execute()) {

                    $mensaje = "Cliente agregado correctamente.";

                } else {

                    $error = "No se pudo agregar el cliente: " . $stmt->error;
                }

                $stmt->close();

            } else {

                $error = "Error preparando la consulta: " . $conexion->error;
            }
        }
    }
}

/* =========================================================
   ESTADÍSTICAS DE ENVÍOS
========================================================= */

$totalEnvios = 0;
$enviosPendientes = 0;
$enviosTransito = 0;
$enviosEntregados = 0;

/* Total */
$resultado = $conexion->query(
    "SELECT COUNT(*) AS total FROM envios"
);

if ($resultado) {
    $fila = $resultado->fetch_assoc();
    $totalEnvios = intval($fila["total"]);
}

/* Pendientes */
$resultado = $conexion->query(
    "SELECT COUNT(*) AS total 
     FROM envios 
     WHERE estado = 'Pendiente'"
);

if ($resultado) {
    $fila = $resultado->fetch_assoc();
    $enviosPendientes = intval($fila["total"]);
}

/* En tránsito */
$resultado = $conexion->query(
    "SELECT COUNT(*) AS total 
     FROM envios 
     WHERE estado IN ('En tránsito', 'En Transito', 'En camino')"
);

if ($resultado) {
    $fila = $resultado->fetch_assoc();
    $enviosTransito = intval($fila["total"]);
}

/* Entregados */
$resultado = $conexion->query(
    "SELECT COUNT(*) AS total 
     FROM envios 
     WHERE estado = 'Entregado'"
);

if ($resultado) {
    $fila = $resultado->fetch_assoc();
    $enviosEntregados = intval($fila["total"]);
}

/* =========================================================
   CONTAR CLIENTES
========================================================= */

$totalClientes = 0;

$resultado = $conexion->query(
    "SELECT COUNT(*) AS total FROM clientes"
);

if ($resultado) {
    $fila = $resultado->fetch_assoc();
    $totalClientes = intval($fila["total"]);
}

/* =========================================================
   CONTAR REPARTIDORES ACTIVOS
========================================================= */

$totalRepartidores = 0;

$resultado = $conexion->query(
    "SELECT COUNT(*) AS total 
     FROM repartidores
     WHERE estado = 'Activo'"
);

if ($resultado) {
    $fila = $resultado->fetch_assoc();
    $totalRepartidores = intval($fila["total"]);
}

/* =========================================================
   OBTENER CLIENTES
========================================================= */

$clientes = [];

$sqlClientes = "
    SELECT 
        id,
        nombre,
        cedula,
        telefono,
        correo,
        direccion,
        fecha_registro
    FROM clientes
    ORDER BY id DESC
";

$resultadoClientes = $conexion->query($sqlClientes);

if ($resultadoClientes) {

    while ($fila = $resultadoClientes->fetch_assoc()) {
        $clientes[] = $fila;
    }
}

/* =========================================================
   OBTENER REPARTIDORES ACTIVOS
========================================================= */

$repartidores = [];

$sqlRepartidores = "
    SELECT
        id,
        nombre,
        codigo,
        vehiculo,
        departamento,
        estado
    FROM repartidores
    WHERE estado = 'Activo'
    ORDER BY nombre ASC
";

$resultadoRepartidores = $conexion->query($sqlRepartidores);

if ($resultadoRepartidores) {

    while ($fila = $resultadoRepartidores->fetch_assoc()) {
        $repartidores[] = $fila;
    }
}

/* =========================================================
   ENVÍOS RECIENTES
========================================================= */

$enviosRecientes = [];

$sqlEnvios = "
    SELECT
        e.id,
        e.codigo,
        e.descripcion,
        e.estado,
        e.fecha_envio,
        e.fecha_entrega,
        e.peso,
        e.tipo,
        e.departamento,
        e.municipio,
        e.direccion,
        e.costo_envio,
        e.total,
        c.nombre AS cliente_nombre,
        r.nombre AS repartidor_nombre
    FROM envios e
    LEFT JOIN clientes c 
        ON e.cliente_id = c.id
    LEFT JOIN repartidores r 
        ON e.repartidor_id = r.id
    ORDER BY e.id DESC
    LIMIT 10
";

$resultadoEnvios = $conexion->query($sqlEnvios);

if ($resultadoEnvios) {

    while ($fila = $resultadoEnvios->fetch_assoc()) {
        $enviosRecientes[] = $fila;
    }
}

/* =========================================================
   EXPORTAR ENVÍOS A CSV
========================================================= */

if (isset($_GET["exportar"]) && $_GET["exportar"] === "csv") {

    header("Content-Type: text/csv; charset=utf-8");
    header(
        "Content-Disposition: attachment; filename=envios_" .
        date("Y-m-d") .
        ".csv"
    );

    $salida = fopen("php://output", "w");

    fputcsv($salida, [
        "ID",
        "Código",
        "Cliente",
        "Repartidor",
        "Descripción",
        "Estado",
        "Fecha envío",
        "Fecha entrega",
        "Peso",
        "Tipo",
        "Departamento",
        "Municipio",
        "Dirección",
        "Costo",
        "Total"
    ]);

    $sqlExportar = "
        SELECT
            e.id,
            e.codigo,
            c.nombre AS cliente,
            r.nombre AS repartidor,
            e.descripcion,
            e.estado,
            e.fecha_envio,
            e.fecha_entrega,
            e.peso,
            e.tipo,
            e.departamento,
            e.municipio,
            e.direccion,
            e.costo_envio,
            e.total
        FROM envios e
        LEFT JOIN clientes c 
            ON e.cliente_id = c.id
        LEFT JOIN repartidores r 
            ON e.repartidor_id = r.id
        ORDER BY e.id DESC
    ";

    $resultadoExportar = $conexion->query($sqlExportar);

    if ($resultadoExportar) {

        while ($fila = $resultadoExportar->fetch_assoc()) {

            fputcsv($salida, [
                $fila["id"],
                $fila["codigo"],
                $fila["cliente"],
                $fila["repartidor"],
                $fila["descripcion"],
                $fila["estado"],
                $fila["fecha_envio"],
                $fila["fecha_entrega"],
                $fila["peso"],
                $fila["tipo"],
                $fila["departamento"],
                $fila["municipio"],
                $fila["direccion"],
                $fila["costo_envio"],
                $fila["total"]
            ]);
        }
    }

    fclose($salida);
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Grupo S.A Logistic - Coordinador</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f9;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #111827;
            color: white;
            padding: 20px 15px;
            z-index: 1000;
        }

        .logo {
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
        }

        .logo span {
            color: #0d6efd;
        }

        .menu-title {
            font-size: 12px;
            color: #9ca3af;
            margin: 20px 10px 8px;
            text-transform: uppercase;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #d1d5db;
            text-decoration: none;
            padding: 12px 13px;
            border-radius: 8px;
            margin-bottom: 5px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #1d4ed8;
            color: white;
        }

        .main {
            margin-left: 250px;
            padding: 25px;
        }

        .topbar {
            background: white;
            border-radius: 12px;
            padding: 18px 22px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
        }

        .topbar h3 {
            margin: 0;
            font-weight: bold;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #0d6efd;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
            height: 100%;
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
            background: #e8f0ff;
            color: #0d6efd;
        }

        .stats-number {
            font-size: 28px;
            font-weight: bold;
            margin-top: 12px;
        }

        .stats-title {
            color: #6b7280;
            font-size: 14px;
        }

        .quick-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
            height: 100%;
            cursor: pointer;
            transition: .2s;
            color: #212529;
        }

        .quick-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 18px rgba(0,0,0,.10);
        }

        .quick-card i {
            font-size: 32px;
            color: #0d6efd;
        }

        .section-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,.05);
            overflow: hidden;
        }

        .section-header {
            padding: 18px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h5 {
            margin: 0;
            font-weight: bold;
        }

        .table-responsive {
            padding: 10px;
        }

        .badge-pendiente {
            background: #fff3cd;
            color: #856404;
        }

        .badge-transito {
            background: #cfe2ff;
            color: #084298;
        }

        .badge-entregado {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-cancelado {
            background: #f8d7da;
            color: #842029;
        }

        .mobile-menu {
            display: none;
        }

        @media (max-width: 900px) {

            .sidebar {
                transform: translateX(-100%);
                transition: .3s;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main {
                margin-left: 0;
                padding: 15px;
            }

            .mobile-menu {
                display: block;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

    </style>

</head>

<body>

<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar" id="sidebar">

    <div class="logo">
        Grupo <span>S.A Logistic</span>
    </div>

    <div class="menu-title">
        Principal
    </div>

    <a href="Cordinador.php" class="active">
        <i class="bi bi-speedometer2"></i>
        <span>Inicio</span>
    </a>

    <a href="#envios">
        <i class="bi bi-box-seam"></i>
        <span>Envíos</span>
    </a>

    <a href="#clientes">
        <i class="bi bi-people"></i>
        <span>Clientes</span>
    </a>

    <a href="#repartidores">
        <i class="bi bi-truck"></i>
        <span>Repartidores</span>
    </a>

    <a href="#seguimiento">
        <i class="bi bi-geo-alt"></i>
        <span>Seguimiento</span>
    </a>

    <a href="#reportes">
        <i class="bi bi-bar-chart"></i>
        <span>Reportes</span>
    </a>

    <div class="menu-title">
        Sistema
    </div>

    <a href="#configuracion">
        <i class="bi bi-gear"></i>
        <span>Configuración</span>
    </a>

    <a href="Cordinador.php?logout=1">
        <i class="bi bi-box-arrow-right"></i>
        <span>Cerrar sesión</span>
    </a>

</div>


<!-- =====================================================
     CONTENIDO PRINCIPAL
===================================================== -->

<div class="main">

    <!-- TOPBAR -->

    <div class="topbar">

        <div class="d-flex align-items-center gap-3">

            <button
                class="btn btn-outline-secondary mobile-menu"
                onclick="toggleSidebar()"
            >
                <i class="bi bi-list"></i>
            </button>

            <div>

                <h3>
                    Panel del Coordinador
                </h3>

                <small class="text-muted">
                    Administración de envíos y logística
                </small>

            </div>

        </div>

        <div class="user-info">

            <div>

                <strong>
                    <?= htmlspecialchars($nombre) ?>
                </strong>

                <br>

                <small class="text-muted">
                    <?= htmlspecialchars($rol) ?>
                </small>

            </div>

            <div class="avatar">

                <?= strtoupper(substr($nombre, 0, 1)) ?>

            </div>

        </div>

    </div>


    <!-- =================================================
         MENSAJES
    ================================================= -->

    <?php if ($mensaje !== ""): ?>

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
        >

            <i class="bi bi-check-circle-fill"></i>

            <?= $mensaje ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >

            <i class="bi bi-exclamation-triangle-fill"></i>

            <?= htmlspecialchars($error) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =================================================
         ESTADÍSTICAS
    ================================================= -->

    <div class="row g-4 mb-4">

        <div class="col-md-6 col-lg-3">

            <div class="stats-card">

                <div class="stats-icon">
                    <i class="bi bi-box-seam"></i>
                </div>

                <div class="stats-number">
                    <?= $totalEnvios ?>
                </div>

                <div class="stats-title">
                    Envíos Totales
                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-3">

            <div class="stats-card">

                <div class="stats-icon">
                    <i class="bi bi-clock"></i>
                </div>

                <div class="stats-number">
                    <?= $enviosPendientes ?>
                </div>

                <div class="stats-title">
                    Envíos Pendientes
                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-3">

            <div class="stats-card">

                <div class="stats-icon">
                    <i class="bi bi-truck"></i>
                </div>

                <div class="stats-number">
                    <?= $enviosTransito ?>
                </div>

                <div class="stats-title">
                    En Tránsito
                </div>

            </div>

        </div>


        <div class="col-md-6 col-lg-3">

            <div class="stats-card">

                <div class="stats-icon">
                    <i class="bi bi-check-circle"></i>
                </div>

                <div class="stats-number">
                    <?= $enviosEntregados ?>
                </div>

                <div class="stats-title">
                    Entregados
                </div>

            </div>

        </div>

    </div>


    <!-- =================================================
         ACCIONES RÁPIDAS
    ================================================= -->

    <div class="mb-4">

        <h4 class="mb-3">
            Acciones rápidas
        </h4>

        <div class="row g-4">


            <!-- ==========================================
                 NUEVO ENVÍO
                 AHORA MANDA A agregar_envio.php
            =========================================== -->

            <div class="col-md-6 col-lg-3">

                <a
                    href="agregar_envio.php"
                    class="quick-card d-block text-decoration-none"
                >

                    <i class="bi bi-plus-circle"></i>

                    <h5 class="mt-3">
                        Nuevo envío
                    </h5>

                    <p class="text-muted mb-0">
                        Registrar un nuevo paquete y asignar repartidor.
                    </p>

                </a>

            </div>


            <!-- ==========================================
                 NUEVO CLIENTE
            =========================================== -->

            <div class="col-md-6 col-lg-3">

                <div
                    class="quick-card"
                    data-bs-toggle="modal"
                    data-bs-target="#modalCliente"
                >

                    <i class="bi bi-person-plus"></i>

                    <h5 class="mt-3">
                        Nuevo cliente
                    </h5>

                    <p class="text-muted mb-0">
                        Registrar un nuevo cliente.
                    </p>

                </div>

            </div>


            <!-- ==========================================
                 REPARTIDORES
            =========================================== -->

            <div class="col-md-6 col-lg-3">

                <a
                    href="#repartidores"
                    class="quick-card d-block text-decoration-none"
                >

                    <i class="bi bi-truck"></i>

                    <h5 class="mt-3">
                        Repartidores
                    </h5>

                    <p class="text-muted mb-0">
                        Consultar repartidores activos.
                    </p>

                </a>

            </div>


            <!-- ==========================================
                 REPORTES
            =========================================== -->

            <div class="col-md-6 col-lg-3">

                <a
                    href="Cordinador.php?exportar=csv"
                    class="quick-card d-block text-decoration-none"
                >

                    <i class="bi bi-file-earmark-excel"></i>

                    <h5 class="mt-3">
                        Reportes
                    </h5>

                    <p class="text-muted mb-0">
                        Descargar reporte de envíos.
                    </p>

                </a>

            </div>

        </div>

    </div>


    <!-- =================================================
         ENVÍOS RECIENTES
    ================================================= -->

    <div
        class="section-card mb-4"
        id="envios"
    >

        <div class="section-header">

            <h5>
                <i class="bi bi-box-seam"></i>
                Envíos recientes
            </h5>

            <a
                href="agregar_envio.php"
                class="btn btn-primary btn-sm"
            >

                <i class="bi bi-plus-circle"></i>

                Nuevo envío

            </a>

        </div>


        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>
                            Código
                        </th>

                        <th>
                            Cliente
                        </th>

                        <th>
                            Repartidor
                        </th>

                        <th>
                            Destino
                        </th>

                        <th>
                            Peso
                        </th>

                        <th>
                            Estado
                        </th>

                        <th>
                            Total
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php if (count($enviosRecientes) > 0): ?>

                    <?php foreach ($enviosRecientes as $envio): ?>

                        <?php

                        $estado = $envio["estado"] ?? "";

                        $claseEstado = "bg-secondary";

                        if ($estado === "Pendiente") {
                            $claseEstado = "badge-pendiente";
                        }

                        if (
                            $estado === "En tránsito" ||
                            $estado === "En Transito" ||
                            $estado === "En camino"
                        ) {
                            $claseEstado = "badge-transito";
                        }

                        if ($estado === "Entregado") {
                            $claseEstado = "badge-entregado";
                        }

                        if ($estado === "Cancelado") {
                            $claseEstado = "badge-cancelado";
                        }

                        ?>

                        <tr>

                            <td>

                                <strong>
                                    <?= htmlspecialchars($envio["codigo"]) ?>
                                </strong>

                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $envio["cliente_nombre"] ?? "Sin cliente"
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $envio["repartidor_nombre"] ?? "Sin asignar"
                                ) ?>
                            </td>

                            <td>

                                <?= htmlspecialchars(
                                    $envio["municipio"] ?? ""
                                ) ?>

                                <br>

                                <small class="text-muted">

                                    <?= htmlspecialchars(
                                        $envio["departamento"] ?? ""
                                    ) ?>

                                </small>

                            </td>

                            <td>
                                <?= number_format(
                                    floatval($envio["peso"]),
                                    2
                                ) ?>
                                kg
                            </td>

                            <td>

                                <span
                                    class="badge <?= $claseEstado ?> p-2"
                                >
                                    <?= htmlspecialchars($estado) ?>
                                </span>

                            </td>

                            <td>

                                <strong>
                                    C$ <?= number_format(
                                        floatval($envio["total"]),
                                        2
                                    ) ?>
                                </strong>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="7"
                            class="text-center text-muted py-4"
                        >

                            <i class="bi bi-box-seam fs-2"></i>

                            <br>

                            Todavía no hay envíos registrados.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- =================================================
         CLIENTES
    ================================================= -->

    <div
        class="section-card mb-4"
        id="clientes"
    >

        <div class="section-header">

            <h5>

                <i class="bi bi-people"></i>

                Clientes

            </h5>

            <button
                class="btn btn-primary btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#modalCliente"
            >

                <i class="bi bi-person-plus"></i>

                Nuevo cliente

            </button>

        </div>


        <div class="table-responsive">

            <table class="table table-hover">

                <thead>

                    <tr>

                        <th>
                            Nombre
                        </th>

                        <th>
                            Cédula
                        </th>

                        <th>
                            Teléfono
                        </th>

                        <th>
                            Correo
                        </th>

                        <th>
                            Dirección
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if (count($clientes) > 0): ?>

                    <?php foreach ($clientes as $cliente): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    $cliente["nombre"]
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $cliente["cedula"] ?? ""
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $cliente["telefono"] ?? ""
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $cliente["correo"] ?? ""
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $cliente["direccion"] ?? ""
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="5"
                            class="text-center text-muted"
                        >
                            No hay clientes registrados.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- =================================================
         REPARTIDORES
    ================================================= -->

    <div
        class="section-card mb-4"
        id="repartidores"
    >

        <div class="section-header">

            <h5>

                <i class="bi bi-truck"></i>

                Repartidores activos

            </h5>

        </div>


        <div class="table-responsive">

            <table class="table table-hover">

                <thead>

                    <tr>

                        <th>
                            Nombre
                        </th>

                        <th>
                            Código
                        </th>

                        <th>
                            Vehículo
                        </th>

                        <th>
                            Departamento
                        </th>

                        <th>
                            Estado
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if (count($repartidores) > 0): ?>

                    <?php foreach ($repartidores as $repartidor): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    $repartidor["nombre"]
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $repartidor["codigo"]
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $repartidor["vehiculo"]
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $repartidor["departamento"]
                                ) ?>
                            </td>

                            <td>

                                <span class="badge bg-success">

                                    <?= htmlspecialchars(
                                        $repartidor["estado"]
                                    ) ?>

                                </span>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="5"
                            class="text-center text-muted"
                        >

                            No hay repartidores activos.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- =================================================
         SEGUIMIENTO
    ================================================= -->

    <div
        class="section-card mb-4"
        id="seguimiento"
    >

        <div class="section-header">

            <h5>

                <i class="bi bi-geo-alt"></i>

                Seguimiento

            </h5>

        </div>

        <div class="p-4">

            <p class="text-muted mb-0">

                Desde aquí podrás consultar el estado de los
                envíos registrados en el sistema.

            </p>

        </div>

    </div>


    <!-- =================================================
         REPORTES
    ================================================= -->

    <div
        class="section-card mb-4"
        id="reportes"
    >

        <div class="section-header">

            <h5>

                <i class="bi bi-bar-chart"></i>

                Reportes

            </h5>

            <a
                href="Cordinador.php?exportar=csv"
                class="btn btn-success btn-sm"
            >

                <i class="bi bi-download"></i>

                Descargar CSV

            </a>

        </div>

        <div class="p-4">

            <div class="row g-3">

                <div class="col-md-4">

                    <div class="alert alert-primary mb-0">

                        <strong>
                            <?= $totalEnvios ?>
                        </strong>

                        <br>

                        Envíos registrados

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="alert alert-warning mb-0">

                        <strong>
                            <?= $enviosPendientes ?>
                        </strong>

                        <br>

                        Pendientes

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="alert alert-success mb-0">

                        <strong>
                            <?= $enviosEntregados ?>
                        </strong>

                        <br>

                        Entregados

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =================================================
         CONFIGURACIÓN
    ================================================= -->

    <div
        class="section-card mb-4"
        id="configuracion"
    >

        <div class="section-header">

            <h5>

                <i class="bi bi-gear"></i>

                Configuración

            </h5>

        </div>

        <div class="p-4">

            <p class="text-muted mb-0">

                Configuración del sistema de logística.

            </p>

        </div>

    </div>

</div>


<!-- =====================================================
     MODAL NUEVO CLIENTE
===================================================== -->

<div
    class="modal fade"
    id="modalCliente"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="bi bi-person-plus"></i>

                    Agregar nuevo cliente

                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <form method="POST">

                <div class="modal-body">

                    <input
                        type="hidden"
                        name="accion"
                        value="agregar_cliente"
                    >


                    <div class="mb-3">

                        <label class="form-label">
                            Nombre completo
                        </label>

                        <input
                            type="text"
                            name="nombre"
                            class="form-control"
                            required
                        >

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Teléfono
                        </label>

                        <input
                            type="text"
                            name="telefono"
                            class="form-control"
                        >

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Correo
                        </label>

                        <input
                            type="email"
                            name="correo"
                            class="form-control"
                        >

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Dirección
                        </label>

                        <textarea
                            name="direccion"
                            class="form-control"
                            rows="3"
                        ></textarea>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-save"></i>

                        Guardar cliente

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =====================================================
     BOOTSTRAP
===================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<script>

    /* ==========================================
       MENÚ PARA CELULAR
    ========================================== */

    function toggleSidebar() {

        const sidebar = document.getElementById("sidebar");

        sidebar.classList.toggle("show");
    }


    /* ==========================================
       CERRAR ALERTAS AUTOMÁTICAMENTE
    ========================================== */

    setTimeout(function () {

        const alertas = document.querySelectorAll(".alert");

        alertas.forEach(function (alerta) {

            const instancia =
                bootstrap.Alert.getOrCreateInstance(alerta);

            instancia.close();

        });

    }, 5000);

</script>

</body>

</html>