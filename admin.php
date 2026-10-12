
<?php
require_once "conexion.php";
require_once "config.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador - Grupo S.A Logistic</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<button type="button" class="menu-btn" id="menuBtn"
        aria-label="Abrir menú">☰</button>

<div class="menu-fondo" id="menuFondo"></div>

<aside class="side-menu" id="sideMenu">
    <div class="menu-header">
        <div>
            <h3>Grupo S.A Logistic</h3>
            <p>Panel de administración</p>
        </div>
        <button type="button" class="menu-close" id="menuClose">×</button>
    </div>

    <nav class="menu-container">
        <a href="admin.php" class="menu-link ">
            <span>🏠</span><span>Inicio</span>
        </a>
    
        <a href="admin_usuarios.php" class="menu-link">
            <span>👤</span><span>Usuarios</span>
        </a>
        <a href="clientes.php" class="menu-link">
            <span>👥</span><span>Clientes</span>
        </a>
        <a href="empleados.php" class="menu-link">
            <span>🧑‍💼</span><span>Empleados</span>
        </a>
        
        <a href="monitoreo.php" class="menu-link">
            <span>📍</span><span>Monitoreo de envíos</span>
        </a>
        <a href="incidencia.php" class="menu-link">
            <span>⚠️</span><span>Incidencias</span>
        </a>
        <a href="reporte.php" class="menu-link">
            <span>📊</span><span>Reportes</span>
        </a>
        <a href="logout.php" class="menu-link">
            <span>↪</span><span>Cerrar sesión</span>
        </a>
    </nav>
</aside>

<main class="contenido">
    <header class="page-head">
        <div>
            <span class="subtitulo">GRUPO S.A LOGISTIC</span>
            <h1>Panel principal del Administrador</h1>
            <p>Bienvenido al sistema de gestión de paquetería y envíos.</p>
        </div>
    </header>

    <section class="bienvenida">
        <div>
            <h2>¡Bienvenido al panel de administración!</h2>
            <p>
                Desde aquí podés acceder a los módulos del sistema,
                administrar la información y consultar los envíos.
            </p>
        </div>
        <div class="icono-bienvenida">📦</div>
    </section>

    <h2 class="titulo-modulos">Módulos del sistema</h2>

    <section class="modulos">
        <a href="admin_usuarios.php" class="modulo">
            <span class="modulo-icono">👤</span>
            <h3>Usuarios</h3>
            <p>Administrar usuarios, roles y estados.</p>
            <span class="ver-modulo">Acceder →</span>
        </a>

        <a href="clientes.php" class="modulo">
            <span class="modulo-icono">👥</span>
            <h3>Clientes</h3>
            <p>Consultar y gestionar los clientes.</p>
            <span class="ver-modulo">Acceder →</span>
        </a>

        <a href="empleados.php" class="modulo">
            <span class="modulo-icono">🧑‍💼</span>
            <h3>Empleados</h3>
            <p>Gestionar la información del personal.</p>
            <span class="ver-modulo">Acceder →</span>
        </a>


        <a href="monitoreo.php" class="modulo">
            <span class="modulo-icono">📍</span>
            <h3>Monitoreo de envíos</h3>
            <p>Acceder al seguimiento de los envíos.</p>
            <span class="ver-modulo">Acceder →</span>
        </a>

        <a href="incidencia.php" class="modulo">
            <span class="modulo-icono">⚠️</span>
            <h3>Incidencias</h3>
            <p>Consultar y gestionar incidencias.</p>
            <span class="ver-modulo">Acceder →</span>
        </a>

        <a href="reporte.php" class="modulo">
            <span class="modulo-icono">📊</span>
            <h3>Reportes</h3>
            <p>Acceder a los reportes del sistema.</p>
            <span class="ver-modulo">Acceder →</span>
        </a>
    </section>
</main>

<script>
(function () {
    const menu = document.getElementById('sideMenu');
    const fondo = document.getElementById('menuFondo');

    function abrir() {
        menu.classList.add('activo');
        fondo.classList.add('activo');
    }

    function cerrar() {
        menu.classList.remove('activo');
        fondo.classList.remove('activo');
    }

    document.getElementById('menuBtn').addEventListener('click', abrir);
    document.getElementById('menuClose').addEventListener('click', cerrar);
    fondo.addEventListener('click', cerrar);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') cerrar();
    });
})();
</script>

</body>
</html>
