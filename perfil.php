<?php

session_start();

require_once "conexion.php";

/* ============================================================
   VERIFICAR SESIÓN
   ============================================================ */

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$usuarioId = (int) $_SESSION['id'];


/* ============================================================
   FUNCIÓN PARA ESCAPAR HTML
   ============================================================ */

function e($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}


/* ============================================================
   OBTENER USUARIO Y REPARTIDOR
   ============================================================ */

$sqlUsuario = "
    SELECT
        u.id,
        u.nombre AS nombre_usuario,
        u.usuario,
        u.rol,
        u.repartidor_id,

        r.id AS id_repartidor,
        r.nombre AS nombre_repartidor,
        r.codigo AS codigo_repartidor,
        r.vehiculo AS vehiculo_repartidor,
        r.departamento AS departamento_repartidor,
        r.estado AS estado_repartidor,
        r.fecha_registro AS fecha_registro_repartidor

    FROM usuarios u

    LEFT JOIN repartidores r
        ON r.id = u.repartidor_id

    WHERE u.id = ?

    LIMIT 1
";

$stmtUsuario = $conexion->prepare($sqlUsuario);

if (!$stmtUsuario) {
    die("Error al preparar la consulta: " . $conexion->error);
}

$stmtUsuario->bind_param("i", $usuarioId);
$stmtUsuario->execute();

$resultadoUsuario = $stmtUsuario->get_result();

$usuario = $resultadoUsuario->fetch_assoc();

$stmtUsuario->close();


if (!$usuario) {
    die("No se encontró el usuario.");
}


/* ============================================================
   VERIFICAR REPARTIDOR
   ============================================================ */

$repartidorId = (int)($usuario['repartidor_id'] ?? 0);

if ($repartidorId <= 0) {
    die("Este usuario no tiene un repartidor asociado.");
}


/* ============================================================
   PROCESAR ACTUALIZACIÓN DEL PERFIL
   ============================================================ */

$mensaje = "";
$tipoMensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_perfil'])) {

    $nombre = trim($_POST['nombre'] ?? '');
    $codigo = trim($_POST['codigo'] ?? '');
    $vehiculo = trim($_POST['vehiculo'] ?? '');
    $departamento = trim($_POST['departamento'] ?? '');

    if ($nombre === '' || $codigo === '' || $vehiculo === '' || $departamento === '') {

        $mensaje = "Todos los campos son obligatorios.";
        $tipoMensaje = "danger";

    } else {

        /*
         * Actualizamos únicamente el registro del repartidor
         * relacionado con el usuario que inició sesión.
         */

        $sqlActualizar = "
            UPDATE repartidores
            SET
                nombre = ?,
                codigo = ?,
                vehiculo = ?,
                departamento = ?
            WHERE id = ?
        ";

        $stmtActualizar = $conexion->prepare($sqlActualizar);

        if (!$stmtActualizar) {

            $mensaje = "Error al preparar la actualización: " . $conexion->error;
            $tipoMensaje = "danger";

        } else {

            $stmtActualizar->bind_param(
                "ssssi",
                $nombre,
                $codigo,
                $vehiculo,
                $departamento,
                $repartidorId
            );

            if ($stmtActualizar->execute()) {

                /*
                 * También actualizamos el nombre de usuarios
                 * para que ambos registros mantengan el mismo nombre.
                 */

                $sqlActualizarUsuario = "
                    UPDATE usuarios
                    SET nombre = ?
                    WHERE id = ?
                ";

                $stmtUsuarioUpdate = $conexion->prepare($sqlActualizarUsuario);

                if ($stmtUsuarioUpdate) {

                    $stmtUsuarioUpdate->bind_param(
                        "si",
                        $nombre,
                        $usuarioId
                    );

                    $stmtUsuarioUpdate->execute();
                    $stmtUsuarioUpdate->close();
                }


                /*
                 * Actualizar la información que se muestra
                 * sin tener que cerrar sesión.
                 */

                $usuario['nombre_repartidor'] = $nombre;
                $usuario['codigo_repartidor'] = $codigo;
                $usuario['vehiculo_repartidor'] = $vehiculo;
                $usuario['departamento_repartidor'] = $departamento;
                $usuario['nombre_usuario'] = $nombre;

                $mensaje = "Los cambios se guardaron correctamente.";
                $tipoMensaje = "success";

            } else {

                $mensaje = "No se pudieron guardar los cambios: " . $stmtActualizar->error;
                $tipoMensaje = "danger";
            }

            $stmtActualizar->close();
        }
    }
}


/* ============================================================
   DATOS PARA MOSTRAR
   ============================================================ */

$nombre = $usuario['nombre_repartidor'] ?? $usuario['nombre_usuario'] ?? 'Sin nombre';

$codigo = $usuario['codigo_repartidor'] ?? 'Sin código';

$vehiculo = $usuario['vehiculo_repartidor'] ?? 'Sin vehículo';

$departamento = $usuario['departamento_repartidor'] ?? 'Sin departamento';

$estado = $usuario['estado_repartidor'] ?? 'Disponible';

$fechaRegistro = $usuario['fecha_registro_repartidor'] ?? '';

$rol = $usuario['rol'] ?? 'Transportista';

$usuarioLogin = $usuario['usuario'] ?? '';


/* ============================================================
   FORMATEAR FECHA
   ============================================================ */

$fechaFormateada = 'No disponible';

if (!empty($fechaRegistro)) {

    $fecha = new DateTime($fechaRegistro);

    $fechaFormateada = $fecha->format('d/m/Y');
}


/* ============================================================
   CLASE DEL ESTADO
   ============================================================ */

$estadoNormalizado = strtolower(trim($estado));

if (
    $estadoNormalizado === 'disponible' ||
    $estadoNormalizado === 'activo' ||
    $estadoNormalizado === 'en buen estado'
) {
    $claseEstado = 'alert-success';
} elseif (
    $estadoNormalizado === 'mantenimiento' ||
    $estadoNormalizado === 'ocupado'
) {
    $claseEstado = 'alert-warning';
} else {
    $claseEstado = 'alert-secondary';
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

    <title>Grupo S.A Logistics - Transportista</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="css/paquete.css"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >
     
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


<!-- ============================================================
     BARRA SUPERIOR
     ============================================================ -->

<nav class="topbar d-flex justify-content-between align-items-center">

    <div class="d-flex align-items-center gap-3">

        <button
            class="menu-btn"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#menuTransportista"
            aria-controls="menuTransportista"
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
            data-bs-target="#staticBackdrop"
        >
            🔔
        </button>


        <div
            class="modal fade"
            id="staticBackdrop"
            data-bs-backdrop="static"
            data-bs-keyboard="false"
            tabindex="-1"
            aria-labelledby="staticBackdropLabel"
            aria-hidden="true"
        >

            <div class="modal-dialog">

                <div class="modal-content">

                    <div class="modal-header">

                        <h1
                            class="modal-title fs-5"
                            id="staticBackdropLabel"
                        >
                            Notificaciones
                        </h1>

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
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


        <!-- PERFIL SUPERIOR -->

        <div class="profile">

            <div class="text-end">

                <div class="profile-name">

                    <?= e($nombre) ?>

                </div>


                <div class="profile-role">

                    <?= e($rol) ?>

                </div>

            </div>

        </div>

    </div>

</nav>



<!-- ============================================================
     CONTENIDO
     ============================================================ -->

<div class="content">


    <!-- ENCABEZADO -->

    <div
        class="d-flex justify-content-between align-items-center mb-4 fade-in"
    >

        <div class="welcome">

            <h2 id="saludo">

                ¡Bienvenido!

            </h2>

        </div>


        <div class="ruta-fecha">

            <i class="bi bi-calendar3"></i>

            <span id="fechaRuta">
                Cargando fecha...
            </span>

        </div>

    </div>



    <!-- ========================================================
         MENSAJE
         ======================================================== -->

    <?php if ($mensaje !== ""): ?>

        <div
            class="alert alert-<?= e($tipoMensaje) ?> alert-dismissible fade show"
            role="alert"
        >

            <?= e($mensaje) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>



    <!-- ========================================================
         INFORMACIÓN DEL PERFIL
         ======================================================== -->

    <div class="main-box mb-4">

        <div class="row align-items-center">


            <!-- DATOS PRINCIPALES -->

            <div class="col-md-4 d-flex align-items-center gap-3">

                <div>

                    <h5 class="mb-1">

                        <?= e($nombre) ?>

                    </h5>


                    <p class="text-muted mb-0">

                        <?= e($rol) ?>

                    </p>


                    <small class="text-muted">

                        <?= e($codigo) ?>

                    </small>

                </div>

            </div>



            <!-- INFORMACIÓN REAL -->

            <div class="col-md-5">


                <p class="mb-2">

                    <i class="bi bi-person-badge me-2"></i>

                    <strong>Usuario:</strong>

                    <?= e($usuarioLogin) ?>

                </p>


                <p class="mb-2">

                    <i class="bi bi-geo-alt me-2"></i>

                    <strong>Departamento:</strong>

                    <?= e($departamento) ?>

                </p>


                <p class="mb-0">

                    <i class="bi bi-calendar-check me-2"></i>

                    <strong>Registrado desde:</strong>

                    <?= e($fechaFormateada) ?>

                </p>

            </div>



            <!-- BOTONES -->

            <div class="col-md-3">


                <button
                    type="button"
                    class="btn btn-primary w-100 mb-2"
                    id="btnEditarPerfil"
                >

                    <i class="bi bi-pencil me-2"></i>

                    Editar perfil

                </button>


                <a
                    href="logout.php"
                    class="btn btn-outline-danger w-100"
                >

                    <i class="bi bi-box-arrow-right me-2"></i>

                    Cerrar sesión

                </a>

            </div>

        </div>

    </div>



    <!-- ========================================================
         VEHÍCULO
         ======================================================== -->

    <div class="main-box">

        <h5 class="mb-4">

            <i class="bi bi-truck me-2"></i>

            Mi vehículo

        </h5>


        <div class="row">


            <!-- IMAGEN -->

            <div class="col-md-4 text-center">

                <img
                    src="img/camion.png"
                    alt="Vehículo"
                    class="img-fluid"
                    style="max-height: 150px;"
                >

            </div>



            <!-- DATOS VEHÍCULO -->

            <div class="col-md-5">

                <div class="row">


                    <div class="col-6">

                        <small class="text-muted">
                            Código
                        </small>

                        <h5>
                            <?= e($codigo) ?>
                        </h5>

                    </div>


                    <div class="col-6">

                        <small class="text-muted">
                            Vehículo
                        </small>

                        <h5>
                            <?= e($vehiculo) ?>
                        </h5>

                    </div>

                </div>


                <hr>


                <div class="row">


                    <div class="col-6">

                        <small class="text-muted">
                            Departamento
                        </small>

                        <h5>
                            <?= e($departamento) ?>
                        </h5>

                    </div>


                    <div class="col-6">

                        <small class="text-muted">
                            Estado
                        </small>

                        <h5>
                            <?= e($estado) ?>
                        </h5>

                    </div>

                </div>

            </div>



            <!-- ESTADO -->

            <div class="col-md-3">

                <h5>
                    Estado actual
                </h5>


                <div class="alert <?= e($claseEstado) ?> text-center">

                    <strong>
                        <?= e($estado) ?>
                    </strong>

                </div>


                <p>

                    <i class="bi bi-check-circle-fill text-success me-2"></i>

                    Vehículo registrado

                </p>


                <p>

                    <i class="bi bi-person-check-fill text-success me-2"></i>

                    Repartidor asignado

                </p>


                <p>

                    <i class="bi bi-card-checklist text-success me-2"></i>

                    Código: <?= e($codigo) ?>

                </p>

            </div>

        </div>



        <!-- FECHA DE REGISTRO -->

        <div class="row mt-4">

            <div class="col-md-6">

                <small class="text-muted">
                    Fecha de registro
                </small>

                <h5>
                    <?= e($fechaFormateada) ?>
                </h5>

            </div>


            <div class="col-md-6">

                <small class="text-muted">
                    Estado del repartidor
                </small>

                <h5>
                    <?= e($estado) ?>
                </h5>

            </div>

        </div>


        <div class="text-center mt-4">

            <button
                type="button"
                class="btn btn-outline-danger px-5"
                id="btnReportarFalla"
            >

                <i class="bi bi-exclamation-triangle me-2"></i>

                Reportar falla

            </button>

        </div>

    </div>

</div>



<!-- ============================================================
     MODAL EDITAR PERFIL
     ============================================================ -->

<div
    class="modal fade"
    id="modalEditarPerfil"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <div>

                    <h5 class="modal-title">

                        Editar perfil

                    </h5>


                    <div class="modal-subtitle">

                        Actualiza tu información

                    </div>

                </div>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>



            <!-- FORMULARIO -->

            <form method="POST" action="perfil.php">

                <div class="modal-body">


                    <!-- NOMBRE -->

                    <div class="mb-3">

                        <label class="form-label">

                            Nombre completo

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="nombre"
                            value="<?= e($nombre) ?>"
                            required
                        >

                    </div>



                    <!-- CÓDIGO -->

                    <div class="mb-3">

                        <label class="form-label">

                            Código del repartidor

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="codigo"
                            value="<?= e($codigo) ?>"
                            required
                        >

                    </div>



                    <!-- VEHÍCULO -->

                    <div class="mb-3">

                        <label class="form-label">

                            Vehículo

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="vehiculo"
                            value="<?= e($vehiculo) ?>"
                            required
                        >

                    </div>



                    <!-- DEPARTAMENTO -->

                    <div class="mb-3">

                        <label class="form-label">

                            Departamento

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="departamento"
                            value="<?= e($departamento) ?>"
                            required
                        >

                    </div>



                    <!-- BOTONES -->

                    <div class="d-flex justify-content-end gap-2">

                        <button
                            type="button"
                            class="btn-cancelar"
                            data-bs-dismiss="modal"
                        >

                            Cancelar

                        </button>


                        <button
                            type="submit"
                            name="guardar_perfil"
                            class="btn-enviar"
                        >

                            <i class="bi bi-check-circle me-1"></i>

                            Guardar cambios

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>



<!-- ============================================================
     MODAL REPORTAR FALLA
     
     NOTA:
     Este formulario todavía NO guarda fallas porque actualmente
     no tenemos una tabla de fallas en la base de datos.
     ============================================================ -->

<div
    class="modal fade"
    id="modalReportarFalla"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <div>

                    <h5 class="modal-title">
                        Reportar falla
                    </h5>

                    <div class="modal-subtitle">
                        Reporta cualquier problema con tu vehículo
                    </div>

                </div>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">


                <div class="mb-3">

                    <label class="form-label">
                        Vehículo
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?= e($vehiculo) ?> - <?= e($codigo) ?>"
                        readonly
                    >

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Tipo de falla
                    </label>

                    <select
                        class="form-control"
                        id="tipoFalla"
                    >

                        <option value="">
                            Seleccionar falla
                        </option>

                        <option value="Motor">
                            Motor
                        </option>

                        <option value="Frenos">
                            Frenos
                        </option>

                        <option value="Luces">
                            Luces
                        </option>

                        <option value="Neumáticos">
                            Neumáticos
                        </option>

                        <option value="Combustible">
                            Combustible
                        </option>

                        <option value="Otro">
                            Otro
                        </option>

                    </select>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Descripción de la falla
                    </label>

                    <textarea
                        class="form-control"
                        id="descripcionFalla"
                        rows="4"
                        placeholder="Describe el problema que presenta el vehículo..."
                    ></textarea>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Evidencia fotográfica
                    </label>

                    <input
                        type="file"
                        class="form-control"
                        id="fotoFalla"
                        accept="image/*"
                    >

                </div>


                <div class="d-flex justify-content-end gap-2">

                    <button
                        type="button"
                        class="btn-cancelar"
                        data-bs-dismiss="modal"
                    >
                        Cancelar
                    </button>


                    <button
                        type="button"
                        class="btn-enviar"
                        id="btnEnviarFalla"
                    >

                        <i class="bi bi-send me-1"></i>

                        Enviar reporte

                    </button>

                </div>

            </div>

        </div>

    </div>

</div>



<!-- ============================================================
     BOOTSTRAP
     ============================================================ -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- ============================================================
     JAVASCRIPT DEL TRANSPORTISTA
     ============================================================ -->

<script
    src="js/transportista.js"
></script>


<!-- ============================================================
     JAVASCRIPT PARA ABRIR MODALES
     ============================================================ -->

<script>

document.addEventListener("DOMContentLoaded", function () {

    const btnEditar = document.getElementById("btnEditarPerfil");

    const modalEditarElement = document.getElementById("modalEditarPerfil");

    if (btnEditar && modalEditarElement) {

        const modalEditar = new bootstrap.Modal(modalEditarElement);

        btnEditar.addEventListener("click", function () {

            modalEditar.show();

        });

    }


    const btnReportar = document.getElementById("btnReportarFalla");

    const modalFallaElement = document.getElementById("modalReportarFalla");

    if (btnReportar && modalFallaElement) {

        const modalFalla = new bootstrap.Modal(modalFallaElement);

        btnReportar.addEventListener("click", function () {

            modalFalla.show();

        });

    }


    /*
     * Fecha actual
     */

    const fechaRuta = document.getElementById("fechaRuta");

    if (fechaRuta) {

        const ahora = new Date();

        const opciones = {
            day: "2-digit",
            month: "2-digit",
            year: "numeric"
        };

        fechaRuta.textContent = ahora.toLocaleDateString(
            "es-NI",
            opciones
        );

    }


    /*
     * Saludo
     */

    const saludo = document.getElementById("saludo");

    if (saludo) {

        const hora = new Date().getHours();

        let texto = "¡Bienvenido!";

        if (hora >= 5 && hora < 12) {

            texto = "¡Buenos días!";

        } else if (hora >= 12 && hora < 18) {

            texto = "¡Buenas tardes!";

        } else {

            texto = "¡Buenas noches!";

        }

        saludo.textContent = texto;

    }


    /*
     * Reloj
     */

    function actualizarReloj() {

        const reloj = document.getElementById("reloj");

        if (!reloj) {
            return;
        }

        const ahora = new Date();

        const horas = String(ahora.getHours()).padStart(2, "0");

        const minutos = String(ahora.getMinutes()).padStart(2, "0");

        const segundos = String(ahora.getSeconds()).padStart(2, "0");

        reloj.textContent =
            horas + ":" +
            minutos + ":" +
            segundos;
    }

    actualizarReloj();

    setInterval(actualizarReloj, 1000);

});

</script>


</body>

</html>