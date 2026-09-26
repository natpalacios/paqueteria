<?php

session_start();

require_once "conexion.php";

/* ==========================================================
   VERIFICAR SESIÓN
   ========================================================== */

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$usuarioId = (int) $_SESSION['id'];


/* ==========================================================
   OBTENER USUARIO Y TRANSPORTISTA
   ========================================================== */

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
";

$stmtUsuario = $conexion->prepare($sqlUsuario);

if (!$stmtUsuario) {
    die("Error en la consulta del usuario: " . $conexion->error);
}

$stmtUsuario->bind_param("i", $usuarioId);
$stmtUsuario->execute();

$resultadoUsuario = $stmtUsuario->get_result();

$usuario = $resultadoUsuario->fetch_assoc();

if (!$usuario) {
    die("No se encontró el usuario.");
}


/* ==========================================================
   ID DEL REPARTIDOR
   ========================================================== */

$repartidorId = (int) ($usuario['repartidor_id'] ?? 0);

if ($repartidorId <= 0) {
    die("El usuario no tiene un transportista asociado.");
}


/* ==========================================================
   OBTENER ENVÍOS DEL TRANSPORTISTA
   ========================================================== */

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
        c.correo AS cliente_correo

    FROM envios e

    LEFT JOIN clientes c
        ON c.id = e.cliente_id

    WHERE e.repartidor_id = ?

    ORDER BY e.id DESC
";

$stmtEnvios = $conexion->prepare($sqlEnvios);

if (!$stmtEnvios) {
    die("Error en la consulta de envíos: " . $conexion->error);
}

$stmtEnvios->bind_param("i", $repartidorId);
$stmtEnvios->execute();

$resultadoEnvios = $stmtEnvios->get_result();

$envios = [];

while ($fila = $resultadoEnvios->fetch_assoc()) {
    $envios[] = $fila;
}


/* ==========================================================
   ESTADÍSTICAS
   ========================================================== */

$totalPaquetes = count($envios);

$entregados = 0;
$incidencias = 0;

foreach ($envios as $envio) {

    $estado = strtolower(trim($envio['estado'] ?? ''));

    if ($estado === 'entregado') {
        $entregados++;
    }

    if ($estado === 'incidencia' || $estado === 'incidente') {
        $incidencias++;
    }
}


/* ==========================================================
   FUNCIONES
   ========================================================== */

function escapar($texto)
{
    return htmlspecialchars(
        (string) $texto,
        ENT_QUOTES,
        'UTF-8'
    );
}


function obtenerDestino($envio)
{
    $partes = [];

    if (!empty($envio['direccion'])) {
        $partes[] = $envio['direccion'];
    }

    if (!empty($envio['municipio'])) {
        $partes[] = $envio['municipio'];
    }

    if (!empty($envio['departamento'])) {
        $partes[] = $envio['departamento'];
    }

    if (empty($partes)) {
        return "Sin destino registrado";
    }

    return implode(", ", $partes);
}


function claseEstado($estado)
{
    $estado = strtolower(trim($estado));

    if ($estado === "entregado") {
        return "estado-entregado";
    }

    if ($estado === "en tránsito" || $estado === "en transito") {
        return "estado-transito";
    }

    if ($estado === "incidencia" || $estado === "incidente") {
        return "estado-incidencia";
    }

    return "estado-pendiente";
}


function formatoFecha($fecha)
{
    if (empty($fecha)) {
        return "Sin fecha";
    }

    $fechaConvertida = strtotime($fecha);

    if ($fechaConvertida === false) {
        return $fecha;
    }

    return date("d/m/Y H:i", $fechaConvertida);
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Grupo S.A Logistics - Mis Paquetes</title>


    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- ICONOS -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- CSS DEL PROYECTO -->

    <link
        rel="stylesheet"
        href="css/paquete.css"
    >
    <style>

    .content {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: relative !important;
        z-index: 1 !important;
    }

    .fade-in {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        transform: none !important;
    }

    .row {
        visibility: visible !important;
    }

    .card {
        visibility: visible !important;
        opacity: 1 !important;
    }

    .main-box {
        visibility: visible !important;
        opacity: 1 !important;
    }

</style>

    <style>

        .estado-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .estado-entregado {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .estado-pendiente {
            background-color: #fff3cd;
            color: #664d03;
        }

        .estado-transito {
            background-color: #cfe2ff;
            color: #084298;
        }

        .estado-incidencia {
            background-color: #f8d7da;
            color: #842029;
        }

        .codigo-paquete {
            font-weight: bold;
            color: #0d6efd;
        }

        .destino-texto {
            max-width: 300px;
        }

        .sin-paquetes {
            text-align: center;
            padding: 50px 20px;
            color: #777;
        }

        .sin-paquetes i {
            font-size: 50px;
            display: block;
            margin-bottom: 15px;
        }

        .profile-name {
            font-weight: 600;
        }

        .profile-role {
            font-size: 13px;
            color: #777;
        }

    </style>

</head>


<body>


<!-- ==========================================================
     MENÚ
     ========================================================== -->

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
                    Cerrar Sesión
                </span>

            </a>


        </div>

    </div>

</div>



<!-- ==========================================================
     BARRA SUPERIOR
     ========================================================== -->

<nav class="topbar d-flex justify-content-between align-items-center">


    <div class="d-flex align-items-center gap-3">


        <button
            class="menu-btn"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#menuTransportista"
        >

            <i class="bi bi-list"></i>

        </button>


        <div id="reloj">
            00:00:00
        </div>


    </div>



    <div class="d-flex align-items-center gap-3">


        <!-- NOTIFICACIONES -->

        <button
            type="button"
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#modalNotificaciones"
        >

            🔔

        </button>



        <!-- USUARIO REAL -->

        <div class="profile">

            <div class="text-end">

                <div class="profile-name">

                    <?php
                    echo escapar($usuario['nombre']);
                    ?>

                </div>


                <div class="profile-role">

                    <?php
                    echo escapar($usuario['rol']);
                    ?>

                </div>

            </div>

        </div>


    </div>

</nav>



<!-- ==========================================================
     CONTENIDO
     ========================================================== -->

<div class="content">


    <!-- BIENVENIDA -->

    <div
        class="d-flex justify-content-between align-items-center mb-4 fade-in"
    >

        <div class="welcome">

            <h2>

                ¡Bienvenido,
                <?php echo escapar($usuario['nombre']); ?>!

            </h2>

        </div>


        <div class="ruta-fecha">

            <i class="bi bi-calendar3"></i>

            <span id="fechaRuta">
                Cargando fecha...
            </span>

        </div>

    </div>



    <!-- ======================================================
         ESTADÍSTICAS
         ====================================================== -->

    <div class="row g-4 mb-4">


        <!-- PAQUETES -->

        <div class="col-md-4">

            <div class="card fade-in">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="text-muted mb-2">
                            Paquetes Asignados
                        </div>

                        <h3 class="stat-number mb-0">

                            <?php echo $totalPaquetes; ?>

                        </h3>

                    </div>


                    <i class="bi bi-box-seam fs-1 text-primary"></i>

                </div>

            </div>

        </div>



        <!-- ENTREGADOS -->

        <div class="col-md-4">

            <div class="card fade-in">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="text-muted mb-2">
                            Entregas realizadas
                        </div>

                        <h3 class="stat-number mb-0">

                            <?php echo $entregados; ?>

                        </h3>

                    </div>


                    <i class="bi bi-check-circle fs-1 text-success"></i>

                </div>

            </div>

        </div>



        <!-- INCIDENCIAS -->

        <div class="col-md-4">

            <div class="card fade-in">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="text-muted mb-2">
                            Incidencias
                        </div>

                        <h3 class="stat-number mb-0">

                            <?php echo $incidencias; ?>

                        </h3>

                    </div>


                    <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>

                </div>

            </div>

        </div>


    </div>



    <!-- ======================================================
         TABLA DE PAQUETES
         ====================================================== -->

    <div class="row g-3">

        <div class="col-12">

            <div class="main-box fade-in">


                <div class="d-flex justify-content-between align-items-center mb-3">

                    <h5 class="mb-0">
                        Mis Paquetes
                    </h5>

                </div>



                <!-- FILTROS -->

                <div class="bg-body-tertiary rounded p-3 mb-3">


                    <div class="row g-2 align-items-center">


                        <div class="col-12 col-lg-7">

                            <div class="d-flex gap-2 flex-wrap">


                                <button
                                    type="button"
                                    class="btn btn-primary filtro-btn"
                                    data-filtro="todos"
                                >
                                    Todos
                                </button>


                                <button
                                    type="button"
                                    class="btn btn-outline-success filtro-btn"
                                    data-filtro="Entregado"
                                >
                                    Entregados
                                </button>


                                <button
                                    type="button"
                                    class="btn btn-outline-warning filtro-btn"
                                    data-filtro="Pendiente"
                                >
                                    Pendientes
                                </button>


                            </div>

                        </div>



                        <div class="col-12 col-lg-5">

                            <div class="input-group">


                                <input
                                    type="text"
                                    id="buscarPaquete"
                                    class="form-control"
                                    placeholder="Buscar por código o cliente"
                                >


                                <button
                                    type="button"
                                    class="btn btn-outline-success"
                                    id="btnBuscar"
                                >

                                    <i class="bi bi-search"></i>

                                    Buscar

                                </button>


                            </div>

                        </div>


                    </div>

                </div>



                <!-- TABLA -->

                <div class="table-responsive">


                    <table class="table table-hover">


                        <thead>

                            <tr>

                                <th>
                                    Código
                                </th>

                                <th>
                                    Cliente
                                </th>

                                <th>
                                    Destino
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th>
                                    Fecha
                                </th>

                                <th>
                                    Actualizar
                                </th>

                            </tr>

                        </thead>



                        <tbody id="tablaPaquetes">


                            <?php if (count($envios) === 0): ?>


                                <tr>

                                    <td colspan="6">

                                        <div class="sin-paquetes">

                                            <i class="bi bi-box-seam"></i>

                                            <h5>
                                                No tienes paquetes asignados
                                            </h5>

                                            <p>
                                                No hay envíos asignados a este transportista.
                                            </p>

                                        </div>

                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach ($envios as $envio): ?>


                                    <?php

                                    $estado = $envio['estado'] ?? 'Pendiente';

                                    $destino = obtenerDestino($envio);

                                    $clase = claseEstado($estado);

                                    $textoBusqueda = strtolower(
                                        ($envio['codigo'] ?? '') .
                                        ' ' .
                                        ($envio['cliente_nombre'] ?? '')
                                    );

                                    ?>


                                    <tr
                                        class="fila-paquete"
                                        data-estado="<?php echo escapar($estado); ?>"
                                        data-busqueda="<?php echo escapar($textoBusqueda); ?>"
                                    >


                                        <!-- CÓDIGO -->

                                        <td>

                                            <span class="codigo-paquete">

                                                <?php
                                                echo escapar($envio['codigo']);
                                                ?>

                                            </span>

                                        </td>



                                        <!-- CLIENTE -->

                                        <td>

                                            <?php

                                            echo escapar(
                                                $envio['cliente_nombre']
                                                ?? 'Sin cliente'
                                            );

                                            ?>

                                        </td>



                                        <!-- DESTINO -->

                                        <td>

                                            <div class="destino-texto">

                                                <?php
                                                echo escapar($destino);
                                                ?>

                                            </div>

                                        </td>



                                        <!-- ESTADO -->

                                        <td>

                                            <span
                                                class="estado-badge <?php echo $clase; ?>"
                                            >

                                                <?php
                                                echo escapar($estado);
                                                ?>

                                            </span>

                                        </td>



                                        <!-- FECHA -->

                                        <td>

                                            <?php

                                            echo escapar(
                                                formatoFecha(
                                                    $envio['fecha_envio']
                                                )
                                            );

                                            ?>

                                        </td>



                                        <!-- ACTUALIZAR -->

                                        <td>

                                            <button
                                                type="button"
                                                class="btn btn-primary btn-sm"
                                                onclick="abrirActualizar(<?php echo (int)$envio['id']; ?>)"
                                            >

                                                <i class="bi bi-arrow-repeat"></i>

                                                Actualizar

                                            </button>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php endif; ?>


                        </tbody>

                    </table>


                </div>

            </div>

        </div>

    </div>


</div>



<!-- ==========================================================
     MODAL ACTUALIZAR
     ========================================================== -->

<div
    class="modal fade"
    id="modalActualizar"
    tabindex="-1"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <div>

                    <h5 class="modal-title">
                        Actualizar estado
                    </h5>

                    <div class="text-muted">
                        Actualiza el estado del paquete
                    </div>

                </div>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>



            <div class="modal-body">


                <input
                    type="hidden"
                    id="envioIdActualizar"
                >


                <div class="mb-3">

                    <label class="form-label">
                        Paquete
                    </label>

                    <input
                        type="text"
                        id="codigoActualizar"
                        class="form-control"
                        readonly
                    >

                </div>



                <div class="mb-3">

                    <label class="form-label">
                        Cliente
                    </label>

                    <input
                        type="text"
                        id="clienteActualizar"
                        class="form-control"
                        readonly
                    >

                </div>



                <div class="mb-3">

                    <label class="form-label">
                        Destino
                    </label>

                    <input
                        type="text"
                        id="destinoActualizar"
                        class="form-control"
                        readonly
                    >

                </div>



                <div class="mb-3">

                    <label class="form-label">
                        Nuevo estado
                    </label>

                    <select
                        id="nuevoEstado"
                        class="form-control"
                    >

                        <option value="">
                            Seleccionar estado
                        </option>

                        <option value="Entregado">
                            Entregado
                        </option>

                        <option value="Incidencia">
                            Incidencia
                        </option>

                    </select>

                </div>



                <div class="mb-3">

                    <label class="form-label">
                        Observación
                    </label>

                    <textarea
                        id="observacion"
                        class="form-control"
                        rows="3"
                        placeholder="Escriba una observación..."
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
                    type="button"
                    class="btn btn-primary"
                    id="btnActualizarEstado"
                >

                    <i class="bi bi-check-circle"></i>

                    Actualizar estado

                </button>

            </div>


        </div>

    </div>

</div>



<!-- ==========================================================
     MODAL NOTIFICACIONES
     ========================================================== -->

<div
    class="modal fade"
    id="modalNotificaciones"
    tabindex="-1"
>

    <div class="modal-dialog">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">
                    Notificaciones
                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">

                No tienes nuevas notificaciones.

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >

                    Entendido

                </button>

            </div>


        </div>

    </div>

</div>



<!-- ==========================================================
     BOOTSTRAP
     ========================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>



<script>

/* ==========================================================
   DATOS REALES DE LOS ENVÍOS
   ========================================================== */

var envios = <?php echo json_encode($envios); ?>;


/* ==========================================================
   RELOJ
   ========================================================== */

function actualizarReloj() {

    var reloj = document.getElementById("reloj");

    if (!reloj) {
        return;
    }

    var ahora = new Date();

    reloj.textContent = ahora.toLocaleTimeString("es-NI");

}

actualizarReloj();

setInterval(actualizarReloj, 1000);


/* ==========================================================
   FECHA
   ========================================================== */

function cargarFecha() {

    var elemento = document.getElementById("fechaRuta");

    if (!elemento) {
        return;
    }

    var fecha = new Date();

    elemento.textContent = fecha.toLocaleDateString(
        "es-NI",
        {
            weekday: "long",
            year: "numeric",
            month: "long",
            day: "numeric"
        }
    );

}

cargarFecha();


/* ==========================================================
   FILTROS
   ========================================================== */

var filtroActual = "todos";


var botonesFiltro =
    document.querySelectorAll(".filtro-btn");


botonesFiltro.forEach(function(boton) {

    boton.addEventListener("click", function() {

        filtroActual = this.getAttribute("data-filtro");

        botonesFiltro.forEach(function(btn) {

            btn.classList.remove("btn-primary");

            if (btn.getAttribute("data-filtro") === "Entregado") {

                btn.classList.add("btn-outline-success");

            } else if (
                btn.getAttribute("data-filtro") === "Pendiente"
            ) {

                btn.classList.add("btn-outline-warning");

            } else {

                btn.classList.add("btn-outline-primary");

            }

        });


        this.classList.remove(
            "btn-outline-primary",
            "btn-outline-success",
            "btn-outline-warning"
        );

        this.classList.add("btn-primary");


        aplicarFiltros();

    });

});


/* ==========================================================
   BUSCADOR
   ========================================================== */

var buscador =
    document.getElementById("buscarPaquete");


if (buscador) {

    buscador.addEventListener("input", function() {

        aplicarFiltros();

    });

}


function aplicarFiltros() {

    var texto = "";

    if (buscador) {
        texto = buscador.value.toLowerCase().trim();
    }


    var filas =
        document.querySelectorAll(".fila-paquete");


    filas.forEach(function(fila) {

        var estado =
            fila.getAttribute("data-estado")
            .toLowerCase()
            .trim();


        var busqueda =
            fila.getAttribute("data-busqueda")
            .toLowerCase();


        var coincideEstado = true;

        var coincideTexto = true;


        if (filtroActual !== "todos") {

            coincideEstado =
                estado === filtroActual.toLowerCase();

        }


        if (texto !== "") {

            coincideTexto =
                busqueda.indexOf(texto) !== -1;

        }


        if (coincideEstado && coincideTexto) {

            fila.style.display = "";

        } else {

            fila.style.display = "none";

        }

    });

}


/* ==========================================================
   ABRIR ACTUALIZACIÓN
   ========================================================== */

function abrirActualizar(id) {

    var envio = null;


    for (var i = 0; i < envios.length; i++) {

        if (Number(envios[i].id) === Number(id)) {

            envio = envios[i];

            break;

        }

    }


    if (!envio) {

        alert("No se encontró el paquete.");

        return;

    }


    document.getElementById(
        "envioIdActualizar"
    ).value = envio.id;


    document.getElementById(
        "codigoActualizar"
    ).value = envio.codigo || "";


    document.getElementById(
        "clienteActualizar"
    ).value =
        envio.cliente_nombre || "Sin cliente";


    document.getElementById(
        "destinoActualizar"
    ).value =
        construirDestino(envio);


    document.getElementById(
        "nuevoEstado"
    ).value = "";


    document.getElementById(
        "observacion"
    ).value = "";


    var modal = new bootstrap.Modal(
        document.getElementById("modalActualizar")
    );


    modal.show();

}


/* ==========================================================
   DESTINO
   ========================================================== */

function construirDestino(envio) {

    var partes = [];


    if (envio.direccion) {

        partes.push(envio.direccion);

    }


    if (envio.municipio) {

        partes.push(envio.municipio);

    }


    if (envio.departamento) {

        partes.push(envio.departamento);

    }


    if (partes.length === 0) {

        return "Sin destino registrado";

    }


    return partes.join(", ");

}


/* ==========================================================
   ACTUALIZAR ESTADO
   ========================================================== */

document
    .getElementById("btnActualizarEstado")
    .addEventListener("click", function() {

        var id =
            document.getElementById(
                "envioIdActualizar"
            ).value;


        var estado =
            document.getElementById(
                "nuevoEstado"
            ).value;


        var observacion =
            document.getElementById(
                "observacion"
            ).value;


        if (!estado) {

            alert("Seleccione un nuevo estado.");

            return;

        }


        /*
         * Por ahora mostramos el aviso.
         *
         * En el siguiente paso conectaremos este botón
         * directamente con UPDATE envios.
         */

        alert(
            "El paquete " +
            document.getElementById("codigoActualizar").value +
            " cambiará a: " +
            estado
        );

    });

</script>


</body>

</html>