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
   DATOS DEL USUARIO
   ============================================================ */

$nombre = $usuario['nombre_repartidor']
    ?? $usuario['nombre_usuario']
    ?? 'Usuario';

$rol = $usuario['rol']
    ?? 'Transportista';

$codigo = $usuario['codigo_repartidor']
    ?? '';


$vehiculo = $usuario['vehiculo_repartidor']
    ?? '';


$departamento = $usuario['departamento_repartidor']
    ?? '';


?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Grupo S.A Logistic - Ayuda y Soporte</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- CSS DEL PROYECTO -->

    <link
        rel="stylesheet"
        href="css/paquete.css"
    >


    <!-- Bootstrap Icons -->

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


            <!-- MI RUTA -->

            <a
                href="rutad.php"
                class="menu-link"
            >

                <i class="bi bi-geo-alt-fill"></i>

                <span>
                    Mi ruta
                </span>

            </a>


            <!-- MIS PAQUETES -->

            <a
                href="paquetes.php"
                class="menu-link"
            >

                <i class="bi bi-box-seam-fill"></i>

                <span>
                    Mis paquetes
                </span>

            </a>


            <!-- AYUDA -->

            <a
                href="Ayuda y soporte.php"
                class="menu-link"
            >

                <i class="bi bi-question-circle-fill"></i>

                <span>
                    Ayuda/Soporte
                </span>

            </a>


            <!-- PERFIL -->

            <a
                href="perfil.php"
                class="menu-link"
            >

                <i class="bi bi-person-fill"></i>

                <span>
                    Mi perfil
                </span>

            </a>


            <!-- CERRAR SESIÓN -->

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


        <!-- BOTÓN MENÚ -->

        <button
            class="menu-btn"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#menuTransportista"
            aria-controls="menuTransportista"
        >

            <i class="bi bi-list"></i>

        </button>


        <!-- RELOJ -->

        <div id="reloj">

            00:00:00

        </div>

    </div>



    <div class="d-flex align-items-center gap-3">


        <!-- ====================================================
             NOTIFICACIONES
             ==================================================== -->

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



        <!-- ====================================================
             PERFIL
             ==================================================== -->

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
     ENCABEZADO
     ============================================================ -->

<div class="content">


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


</div>



<!-- ============================================================
     AYUDA Y SOPORTE
     ============================================================ -->

<div class="content">


    <div class="welcome mb-4">

        <h2>

            ¿En qué podemos ayudarte?

        </h2>


        <p>

            Encuentra ayuda y soporte para realizar tus entregas.

        </p>

    </div>



    <div class="row g-4">


        <!-- ====================================================
             CONTACTAR SUPERVISOR
             ==================================================== -->

        <div class="col-md-4">

            <div
                class="main-box h-100"
                role="button"
                data-bs-toggle="modal"
                data-bs-target="#modalSupervisor"
                style="cursor:pointer;"
            >

                <div class="d-flex align-items-start gap-3">


                    <i
                        class="bi bi-telephone-fill fs-3 text-primary"
                    ></i>


                    <div>

                        <h5>
                            Contactar supervisor
                        </h5>


                        <p class="text-muted mb-0">

                            Habla con tu supervisor

                        </p>

                    </div>

                </div>

            </div>

        </div>



        <!-- ====================================================
             REPORTAR PROBLEMA
             ==================================================== -->

        <div class="col-md-4">

            <div
                class="main-box h-100"
                role="button"
                data-bs-toggle="modal"
                data-bs-target="#modalProblema"
                style="cursor:pointer;"
            >

                <div class="d-flex align-items-start gap-3">


                    <i
                        class="bi bi-exclamation-circle-fill fs-3 text-danger"
                    ></i>


                    <div>

                        <h5>
                            Reportar problema
                        </h5>


                        <p class="text-muted mb-0">

                            Informa una incidencia

                        </p>

                    </div>

                </div>

            </div>

        </div>



        <!-- ====================================================
             PREGUNTAS FRECUENTES
             ==================================================== -->

        <div class="col-md-4">

            <div
                class="main-box h-100"
                role="button"
                data-bs-toggle="modal"
                data-bs-target="#modalPreguntas"
                style="cursor:pointer;"
            >

                <div class="d-flex align-items-start gap-3">


                    <i
                        class="bi bi-question-circle-fill fs-3 text-primary"
                    ></i>


                    <div>

                        <h5>
                            Preguntas frecuentes
                        </h5>


                        <p class="text-muted mb-0">

                            Resuelve tus dudas

                        </p>

                    </div>

                </div>

            </div>

        </div>



        <!-- ====================================================
             SOPORTE TÉCNICO
             ==================================================== -->

        <div class="col-md-4">

            <div
                class="main-box h-100"
                role="button"
                data-bs-toggle="modal"
                data-bs-target="#modalTecnico"
                style="cursor:pointer;"
            >

                <div class="d-flex align-items-start gap-3">


                    <i
                        class="bi bi-gear-fill fs-3 text-primary"
                    ></i>


                    <div>

                        <h5>
                            Soporte técnico
                        </h5>


                        <p class="text-muted mb-0">

                            Problemas con la aplicación

                        </p>

                    </div>

                </div>

            </div>

        </div>



        <!-- ====================================================
             EMERGENCIA
             ==================================================== -->

        <div class="col-md-4">

            <div
                class="main-box h-100"
                role="button"
                data-bs-toggle="modal"
                data-bs-target="#modalEmergencia"
                style="cursor:pointer;"
            >

                <div class="d-flex align-items-start gap-3">


                    <i
                        class="bi bi-exclamation-triangle-fill fs-3 text-danger"
                    ></i>


                    <div>

                        <h5>
                            Emergencia
                        </h5>


                        <p class="text-muted mb-0">

                            Llama de inmediato

                        </p>

                    </div>

                </div>

            </div>

        </div>



        <!-- ====================================================
             HISTORIAL
             ==================================================== -->

        <div class="col-md-4">

            <div
                class="main-box h-100"
                role="button"
                data-bs-toggle="modal"
                data-bs-target="#modalHistorial"
                style="cursor:pointer;"
            >

                <div class="d-flex align-items-start gap-3">


                    <i
                        class="bi bi-file-text-fill fs-3 text-primary"
                    ></i>


                    <div>

                        <h5>
                            Historial de reportes
                        </h5>


                        <p class="text-muted mb-0">

                            Consulta tus reportes

                        </p>

                    </div>

                </div>

            </div>

        </div>



        <!-- ====================================================
             INFORMACIÓN DEL TRANSPORTISTA
             ==================================================== -->

        <div class="col-12">

            <div class="main-box">

                <div class="d-flex align-items-center gap-4">


                    <i
                        class="bi bi-person-circle fs-1 text-primary"
                    ></i>


                    <div>


                        <h5 class="mb-1">

                            Información del transportista

                        </h5>


                        <p class="mb-1">

                            <strong>
                                Nombre:
                            </strong>

                            <?= e($nombre) ?>

                        </p>


                        <p class="mb-1">

                            <strong>
                                Código:
                            </strong>

                            <?= e($codigo) ?>

                        </p>


                        <p class="mb-1">

                            <strong>
                                Vehículo:
                            </strong>

                            <?= e($vehiculo) ?>

                        </p>


                        <p class="mb-0">

                            <strong>
                                Departamento:
                            </strong>

                            <?= e($departamento) ?>

                        </p>


                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



<!-- ============================================================
     MODAL CONTACTAR SUPERVISOR
     ============================================================ -->

<div
    class="modal fade"
    id="modalSupervisor"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">

                    Contactar supervisor

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">

                <p>

                    Si necesitas comunicarte con tu supervisor,
                    utiliza los medios de contacto proporcionados
                    por la empresa.

                </p>


                <div class="alert alert-info">

                    <i class="bi bi-info-circle me-2"></i>

                    Los datos de contacto del supervisor todavía
                    no están registrados en la base de datos.

                </div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >

                    Cerrar

                </button>

            </div>

        </div>

    </div>

</div>



<!-- ============================================================
     MODAL REPORTAR PROBLEMA
     ============================================================ -->

<div
    class="modal fade"
    id="modalProblema"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">

                    Reportar problema

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">


                <p>

                    Indica el problema que estás presentando.

                </p>


                <div class="mb-3">

                    <label class="form-label">

                        Tipo de problema

                    </label>


                    <select
                        class="form-select"
                        id="tipoProblema"
                    >

                        <option value="">

                            Seleccionar

                        </option>


                        <option value="Ruta">

                            Problema con la ruta

                        </option>


                        <option value="Paquete">

                            Problema con un paquete

                        </option>


                        <option value="Aplicación">

                            Problema con la aplicación

                        </option>


                        <option value="Vehículo">

                            Problema con el vehículo

                        </option>


                        <option value="Otro">

                            Otro

                        </option>

                    </select>

                </div>


                <div class="mb-3">

                    <label class="form-label">

                        Descripción

                    </label>


                    <textarea
                        class="form-control"
                        id="descripcionProblema"
                        rows="4"
                        placeholder="Describe el problema..."
                    ></textarea>

                </div>


                <div class="alert alert-warning mb-0">

                    <i class="bi bi-exclamation-triangle me-2"></i>

                    El sistema todavía no cuenta con una tabla
                    para almacenar reportes.

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
                    id="btnEnviarProblema"
                >

                    Enviar

                </button>

            </div>

        </div>

    </div>

</div>



<!-- ============================================================
     MODAL PREGUNTAS FRECUENTES
     ============================================================ -->

<div
    class="modal fade"
    id="modalPreguntas"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">

                    Preguntas frecuentes

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">


                <div class="accordion" id="accordionPreguntas">


                    <div class="accordion-item">

                        <h2 class="accordion-header">

                            <button
                                class="accordion-button"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#pregunta1"
                            >

                                ¿Cómo puedo ver mis paquetes?

                            </button>

                        </h2>


                        <div
                            id="pregunta1"
                            class="accordion-collapse collapse show"
                            data-bs-parent="#accordionPreguntas"
                        >

                            <div class="accordion-body">

                                Ingresa a la opción
                                <strong>Mis paquetes</strong>
                                desde el menú lateral para consultar
                                los paquetes asignados a tu usuario.

                            </div>

                        </div>

                    </div>



                    <div class="accordion-item">

                        <h2 class="accordion-header">

                            <button
                                class="accordion-button collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#pregunta2"
                            >

                                ¿Cómo puedo ver mi ruta?

                            </button>

                        </h2>


                        <div
                            id="pregunta2"
                            class="accordion-collapse collapse"
                            data-bs-parent="#accordionPreguntas"
                        >

                            <div class="accordion-body">

                                Ingresa a
                                <strong>Mi ruta</strong>
                                para consultar los envíos que tienes
                                asignados.

                            </div>

                        </div>

                    </div>



                    <div class="accordion-item">

                        <h2 class="accordion-header">

                            <button
                                class="accordion-button collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#pregunta3"
                            >

                                ¿Dónde puedo actualizar mis datos?

                            </button>

                        </h2>


                        <div
                            id="pregunta3"
                            class="accordion-collapse collapse"
                            data-bs-parent="#accordionPreguntas"
                        >

                            <div class="accordion-body">

                                Ingresa a
                                <strong>Mi perfil</strong>
                                y selecciona
                                <strong>Editar perfil</strong>.

                            </div>

                        </div>

                    </div>



                    <div class="accordion-item">

                        <h2 class="accordion-header">

                            <button
                                class="accordion-button collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#pregunta4"
                            >

                                ¿Qué hago si tengo un problema?

                            </button>

                        </h2>


                        <div
                            id="pregunta4"
                            class="accordion-collapse collapse"
                            data-bs-parent="#accordionPreguntas"
                        >

                            <div class="accordion-body">

                                Puedes utilizar la opción
                                <strong>Reportar problema</strong>
                                para indicar la incidencia que estás
                                presentando.

                            </div>

                        </div>

                    </div>


                </div>

            </div>

        </div>

    </div>

</div>



<!-- ============================================================
     MODAL SOPORTE TÉCNICO
     ============================================================ -->

<div
    class="modal fade"
    id="modalTecnico"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">

                    Soporte técnico

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">

                <p>

                    Si tienes problemas con el funcionamiento
                    de la aplicación, describe la situación para
                    poder reportarla al encargado.

                </p>


                <div class="alert alert-info">

                    <i class="bi bi-gear me-2"></i>

                    Para soporte técnico utiliza la opción
                    <strong>Reportar problema</strong>.

                </div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >

                    Cerrar

                </button>

            </div>

        </div>

    </div>

</div>



<!-- ============================================================
     MODAL EMERGENCIA
     ============================================================ -->

<div
    class="modal fade"
    id="modalEmergencia"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>

                    Emergencia

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body text-center">


                <i
                    class="bi bi-telephone-fill text-danger"
                    style="font-size: 50px;"
                ></i>


                <h5 class="mt-3">

                    Atención de emergencia

                </h5>


                <p class="text-muted">

                    Comunícate con el encargado de la empresa
                    utilizando el número de emergencia proporcionado
                    por la organización.

                </p>


                <div class="alert alert-warning">

                    El número de emergencia todavía no está
                    registrado en la base de datos.

                </div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >

                    Cerrar

                </button>

            </div>

        </div>

    </div>

</div>



<!-- ============================================================
     MODAL HISTORIAL
     ============================================================ -->

<div
    class="modal fade"
    id="modalHistorial"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">

                    Historial de reportes

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body text-center">


                <i
                    class="bi bi-file-text text-primary"
                    style="font-size: 50px;"
                ></i>


                <h5 class="mt-3">

                    Sin reportes registrados

                </h5>


                <p class="text-muted">

                    El historial estará disponible cuando
                    el sistema tenga registrada una tabla
                    de reportes.

                </p>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >

                    Cerrar

                </button>

            </div>

        </div>

    </div>

</div>



<!-- ============================================================
     BOOTSTRAP JS
     ============================================================ -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- ============================================================
     JAVASCRIPT TRANSPORTISTA
     ============================================================ -->

<script
    src="js/transportista.js"
></script>


<!-- ============================================================
     JAVASCRIPT DE LA PÁGINA
     ============================================================ -->

<script>

document.addEventListener("DOMContentLoaded", function () {


    /* ========================================================
       RELOJ
       ======================================================== */

    function actualizarReloj() {

        const reloj = document.getElementById("reloj");

        if (!reloj) {
            return;
        }


        const ahora = new Date();


        const horas = String(
            ahora.getHours()
        ).padStart(2, "0");


        const minutos = String(
            ahora.getMinutes()
        ).padStart(2, "0");


        const segundos = String(
            ahora.getSeconds()
        ).padStart(2, "0");


        reloj.textContent =
            horas + ":" +
            minutos + ":" +
            segundos;

    }


    actualizarReloj();

    setInterval(actualizarReloj, 1000);



    /* ========================================================
       FECHA
       ======================================================== */

    const fechaRuta = document.getElementById("fechaRuta");


    if (fechaRuta) {

        const ahora = new Date();


        fechaRuta.textContent =
            ahora.toLocaleDateString(
                "es-NI",
                {
                    day: "2-digit",
                    month: "2-digit",
                    year: "numeric"
                }
            );

    }



    /* ========================================================
       SALUDO
       ======================================================== */

    const saludo = document.getElementById("saludo");


    if (saludo) {

        const hora = new Date().getHours();


        if (hora >= 5 && hora < 12) {

            saludo.textContent = "¡Buenos días!";

        }

        else if (hora >= 12 && hora < 18) {

            saludo.textContent = "¡Buenas tardes!";

        }

        else {

            saludo.textContent = "¡Buenas noches!";

        }

    }



    /* ========================================================
       BOTÓN REPORTAR PROBLEMA
       ======================================================== */

    const btnEnviarProblema =
        document.getElementById("btnEnviarProblema");


    if (btnEnviarProblema) {

        btnEnviarProblema.addEventListener(
            "click",
            function () {

                const tipo =
                    document.getElementById(
                        "tipoProblema"
                    ).value;


                const descripcion =
                    document.getElementById(
                        "descripcionProblema"
                    ).value.trim();


                if (tipo === "") {

                    alert(
                        "Selecciona el tipo de problema."
                    );

                    return;
                }


                if (descripcion === "") {

                    alert(
                        "Escribe una descripción del problema."
                    );

                    return;
                }


                alert(
                    "El reporte está preparado, pero todavía no se guarda en la base de datos porque no existe una tabla de reportes."
                );

            }
        );

    }

});

</script>


</body>

</html>