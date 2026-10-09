<?php

session_start();

require_once "conexion.php";


if (!isset($_SESSION["id"])) {
    header("Location: index.php");
    exit;
}

$nombre = $_SESSION["nombre"] ?? "Coordinador";
$usuario = $_SESSION["usuario"] ?? "";
$rol = $_SESSION["rol"] ?? "Coordinador";

$mensaje = "";
$error = "";


if (isset($_GET["logout"])) {
    session_unset();
    session_destroy();

    header("Location: index.php");
    exit;
}


if (isset($_GET["envio"]) && $_GET["envio"] === "creado") {

    $codigoCreado = $_GET["codigo"] ?? "";

    if ($codigoCreado !== "") {
        $mensaje = "El envío " . htmlspecialchars($codigoCreado) . " fue registrado correctamente.";
    } else {
        $mensaje = "El envío fue registrado correctamente.";
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accion"])) {

    $accion = $_POST["accion"];

    if ($accion === "agregar_cliente") {

        $nombreCliente = trim($_POST["nombre"] ?? "");
        $cedula = trim($_POST["cedula"] ?? "");
        $telefono = trim($_POST["telefono"] ?? "");
        $correo = trim($_POST["correo"] ?? "");
        $direccionCliente = trim($_POST["direccion"] ?? "");

        if ($nombreCliente === "") {

            $error = "El nombre del cliente es obligatorio.";

        } else {

            $sql = "INSERT INTO clientes 
                    (nombre,cedula, telefono, correo, direccion)
                    VALUES (?, ?, ?, ?,?)";

            $stmt = $conexion->prepare($sql);

            if ($stmt) {

                $stmt->bind_param(
                 "sssss",
                 $nombreCliente,
                 $cedula,
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



$totalEnvios = 0;
$enviosPendientes = 0;
$enviosTransito = 0;
$enviosEntregados = 0;


$resultado = $conexion->query(
    "SELECT COUNT(*) AS total FROM envios"
);

if ($resultado) {
    $fila = $resultado->fetch_assoc();
    $totalEnvios = intval($fila["total"]);
}


$resultado = $conexion->query(
    "SELECT COUNT(*) AS total 
     FROM envios 
     WHERE estado = 'Pendiente'"
);

if ($resultado) {
    $fila = $resultado->fetch_assoc();
    $enviosPendientes = intval($fila["total"]);
}


$resultado = $conexion->query(
    "SELECT COUNT(*) AS total 
     FROM envios 
     WHERE estado IN ('En tránsito', 'En Transito', 'En camino')"
);

if ($resultado) {
    $fila = $resultado->fetch_assoc();
    $enviosTransito = intval($fila["total"]);
}


$resultado = $conexion->query(
    "SELECT COUNT(*) AS total 
     FROM envios 
     WHERE estado = 'Entregado'"
);

if ($resultado) {
    $fila = $resultado->fetch_assoc();
    $enviosEntregados = intval($fila["total"]);
}



$totalClientes = 0;

$resultado = $conexion->query(
    "SELECT COUNT(*) AS total FROM clientes"
);

if ($resultado) {
    $fila = $resultado->fetch_assoc();
    $totalClientes = intval($fila["total"]);
}



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
        background: #f4f8fc;
        color: #172b4d;
        overflow-x: hidden;
    }

  

    .sidebar {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;

        width: 260px !important;
        height: 100vh !important;

        background: #063b70 !important;
        color: white !important;

        z-index: 1050 !important;

        padding: 0 !important;

        transform: translateX(-100%) !important;

        transition: transform .3s ease !important;

        display: block !important;
    }

    .sidebar.menu-abierto {
        transform: translateX(0) !important;
    }

    .sidebar-header {
        padding: 20px;
        border-bottom: 1px solid rgba(255,255,255,.15);
    }

    .sidebar-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: bold;
        color: white;
    }

    

    .profile-section {
        display: flex;
        align-items: center;
        gap: 12px;

        padding: 20px;

        border-bottom: 1px solid rgba(255,255,255,.15);
    }

    .profile-icon {
        font-size: 40px;
        color: white;
    }

    .profile-info h5 {
        margin: 0;
        font-size: 14px;
        font-weight: bold;
        color: white;
    }

    .profile-info span {
        color: #c9def3;
        font-size: 12px;
    }

  

    .menu {
        list-style: none;
        padding: 15px;
        margin: 0;
    }

    .menu li {
        margin-bottom: 6px;
    }

    .menu .nav-link {
        display: flex;
        align-items: center;
        gap: 13px;

        padding: 13px 15px;

        border-radius: 8px;

        color: white !important;

        text-decoration: none;

        font-size: 14px;

        transition: all .25s ease;
    }

    .menu .nav-link i {
        font-size: 18px;
        width: 22px;
    }

    .menu .nav-link:hover {
        background: rgba(255,255,255,.12);
        color: white !important;
        padding-left: 20px;
    }

    .menu .nav-link.active {
        background: #0879df;
        color: white !important;

        box-shadow: 0 4px 10px rgba(0,0,0,.15);
    }

  

    .logout-section {
        position: absolute;

        bottom: 25px;
        left: 15px;
        right: 15px;
    }

    .logout {
        display: flex;
        align-items: center;
        gap: 13px;

        padding: 13px 15px;

        border-radius: 8px;

        color: white !important;

        text-decoration: none;

        font-size: 14px;
    }

    .logout:hover {
        background: rgba(255,255,255,.12);
        color: white !important;
    }

    .logout i {
        font-size: 18px;
    }

   

    .menu-overlay {
        position: fixed;

        top: 0;
        left: 0;

        width: 100%;
        height: 100vh;

        background: rgba(0,0,0,.35);

        z-index: 1040;

        opacity: 0;
        visibility: hidden;

        transition: all .3s ease;
    }

    .menu-overlay.activo {
        opacity: 1;
        visibility: visible;
    }

   

    .main,
    .main-content {
        margin-left: 0 !important;

        width: 100% !important;

        padding: 25px;
    }


    .topbar {
        background: white;

        border-radius: 12px;

        padding: 15px 20px;

        margin-bottom: 25px;

        display: flex;

        align-items: center;

        gap: 18px;

        box-shadow: 0 2px 10px rgba(0,0,0,.05);
    }

    .topbar > div:nth-child(2) {
        flex: 1;
    }

    .topbar h2 {
        margin: 0;

        font-size: 24px;

        font-weight: bold;
    }

    .topbar p {
        margin: 4px 0 0;

        color: #718096;

        font-size: 14px;
    }

  

    .btn-menu {
        width: 45px !important;
        height: 45px !important;

        min-width: 45px;

        border: none !important;

        border-radius: 9px !important;

        background: #063b70 !important;

        color: white !important;

        font-size: 23px !important;

        display: flex !important;

        align-items: center !important;

        justify-content: center !important;

        cursor: pointer;

        flex-shrink: 0;
    }

    .btn-menu:hover {
        background: #0879df !important;

        transform: scale(1.05);
    }


    .user-info {
        display: flex;

        align-items: center;

        gap: 10px;
    }

    .user-text {
        display: flex;

        flex-direction: column;

        text-align: right;
    }

    .user-text strong {
        font-size: 14px;
    }

    .user-text small {
        color: #777;

        font-size: 12px;
    }

    .user-info > i {
        font-size: 35px;

        color: #0879df;
    }

  

    .stats-card {
        background: white;

        border-radius: 12px;

        padding: 20px;

        box-shadow: 0 2px 10px rgba(0,0,0,.05);

        height: 100%;

        border: 1px solid #e3e8ed;
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

        color: #0879df;
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

        border: 1px solid #e3e8ed;
    }

    .quick-card:hover {
        transform: translateY(-3px);

        box-shadow: 0 5px 18px rgba(0,0,0,.10);
    }

    .quick-card i {
        font-size: 32px;

        color: #0879df;
    }

   

    .section-card {
        background: white;

        border-radius: 12px;

        box-shadow: 0 2px 10px rgba(0,0,0,.05);

        overflow: hidden;

        border: 1px solid #e3e8ed;
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

   

    @media (max-width: 900px) {

        .main,
        .main-content {
            padding: 15px;
        }

        .topbar {
            flex-wrap: wrap;
        }

        .user-info {
            margin-left: auto;
        }
    }

</style>

</head>

<body>



<div class="sidebar" id="sidebar">

    <div class="sidebar-header">

        <h3>
            Grupo S.A Logistic
        </h3>

    </div>

    <div class="profile-section">

        <i class="bi bi-person-circle profile-icon"></i>

        <div class="profile-info">

            <h5>
                <?= htmlspecialchars($nombre) ?>
            </h5>

            <span>
                <?= htmlspecialchars($rol) ?>
            </span>

        </div>

    </div>

    <ul class="menu">

        <li>
            <a href="Cordinador.php" class="nav-link active">

                <i class="bi bi-speedometer2"></i>

                <span>
                    Inicio
                </span>

            </a>
        </li>

        <li>
            <a href="#envios" class="nav-link">

                <i class="bi bi-box-seam"></i>

                <span>
                    Envíos
                </span>

            </a>
        </li>

        <li>
            <a href="#clientes" class="nav-link">

                <i class="bi bi-people"></i>

                <span>
                    Clientes
                </span>

            </a>
        </li>

        <li>
            <a href="#repartidores" class="nav-link">

                <i class="bi bi-truck"></i>

                <span>
                    Repartidores
                </span>

            </a>
        </li>

        <li>
            <a href="#reportes" class="nav-link">

                <i class="bi bi-bar-chart"></i>

                <span>
                    Reportes
                </span>

            </a>
        </li>

    </ul>

    <div class="logout-section">

        <a
            href="Cordinador.php?logout=1"
            class="logout"
        >

            <i class="bi bi-box-arrow-right"></i>

            <span>
                Cerrar sesión
            </span>

        </a>

    </div>

</div>



<div
    class="menu-overlay"
    id="menuOverlay"
></div>



<div class="main-content">

   

    <div class="topbar">

        <button
            type="button"
            class="btn-menu"
            id="btnMenu"
        >
            <i class="bi bi-list"></i>
        </button>

        <div>

            <h2>
                Panel del Coordinador
            </h2>

            <p>
                Administración de envíos y logística
            </p>

        </div>

        <div class="user-info">

            <div class="user-text">

                <strong>
                    <?= htmlspecialchars($nombre) ?>
                </strong>

                <small>
                    <?= htmlspecialchars($rol) ?>
                </small>

            </div>

            <i class="bi bi-person-circle"></i>

        </div>

    </div>

    

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

   

    <div class="mb-4">

        <h4 class="mb-3">
            Acciones rápidas
        </h4>

        <div class="row g-4">

            <!-- NUEVO ENVÍO -->

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

</div>


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
                            Cedula
                        </label>  

                        <input
                            type="text"
                            name="cedula"
                            class="form-control"
                            placeholder="Ejemplo: 001-000000-0000A"
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



<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>



<script>

document.addEventListener("DOMContentLoaded", function () {

    const sidebar = document.getElementById("sidebar");
    const btnMenu = document.getElementById("btnMenu");
    const menuOverlay = document.getElementById("menuOverlay");


    function abrirMenu() {

        sidebar.classList.add("menu-abierto");

        menuOverlay.classList.add("activo");

    }


    function cerrarMenu() {

        sidebar.classList.remove("menu-abierto");

        menuOverlay.classList.remove("activo");

    }




    btnMenu.addEventListener("click", function () {

        if (sidebar.classList.contains("menu-abierto")) {

            cerrarMenu();

        } else {

            abrirMenu();

        }

    });



    menuOverlay.addEventListener("click", function () {

        cerrarMenu();

    });



    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {

            cerrarMenu();

        }

    });


    document.querySelectorAll(".sidebar .nav-link").forEach(function (enlace) {

        enlace.addEventListener("click", function () {

            cerrarMenu();

        });

    });

});

</script>


<script>

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