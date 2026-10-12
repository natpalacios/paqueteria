<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reportes</title>

    <link rel="stylesheet" href="css/reporte.css">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<main class="contenido">

<button id="openMenu" class="btn-menu">
    <i class="fa-solid fa-bars"></i>
</button>

 <section class="titulo">

    <div class="titulo-icono">
        <i class="fa-solid fa-file-lines"></i>
    </div>

    <div>
        <h1>Reportes</h1>
        <p>Consulte información detallada sobre paquetes, incidencias y entregas.</p>
    </div>

</section>

<!-- MENÚ LATERAL -->
<aside id="sideMenu" class="side-menu">

    <div class="menu-content">

        <div class="menu-header">

            <div>
                <h5>Grupo S.A Logistics</h5>
                <div class="company-subtitle">
                    Paquetería y Envíos
                </div>
            </div>

            <button
                type="button"
                id="closeMenu"
                class="menu-close"
                aria-label="Cerrar menú">
                ×
            </button>

        </div>

        <div class="menu-container">

           <a href="admin.php" class="menu-link">
            <span>🏠</span><span>Inicio</span>
        </a>
    
        <a href="admin_usuarios.php" class="menu-link ">
            <span>👤</span><span>Usuarios</span>
        </a>
        <a href="clientes.php" class="menu-link ">
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

        </div>

    </div>

</aside>

    </section>


    <section class="opciones-reportes">


        <div class="reporte-card">

            <div class="reporte-icono paquetes">
                <i class="fa-solid fa-box"></i>
            </div>

            <div class="reporte-info">

                <h2>Reporte de paquetes</h2>

                <p>
                    Consulte el estado y la información de los paquetes
                    registrados en el sistema.
                </p>

            </div>

            <button
                class="btn-ver"
                onclick="mostrarReporte('paquetes')">

                <i class="fa-solid fa-arrow-right"></i>
                Ver reporte

            </button>

        </div>

        <div class="reporte-card">

            <div class="reporte-icono incidencias">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>

            <div class="reporte-info">

                <h2>Reporte de incidencias</h2>

                <p>
                    Consulte el historial de problemas registrados
                    durante las entregas.
                </p>

            </div>

            <button
                class="btn-ver"
                onclick="mostrarReporte('incidencias')">

                <i class="fa-solid fa-arrow-right"></i>
                Ver reporte

            </button>

        </div>

        <div class="reporte-card">

            <div class="reporte-icono entregas">
                <i class="fa-solid fa-truck"></i>
            </div>

            <div class="reporte-info">

                <h2>Reporte de entregas</h2>

                <p>
                    Consulte las entregas realizadas, pendientes
                    y aquellas que se encuentran en ruta.
                </p>

            </div>

            <button
                class="btn-ver"
                onclick="mostrarReporte('entregas')">

                <i class="fa-solid fa-arrow-right"></i>
                Ver reporte

            </button>

        </div>

    </section>

    <section
        id="contenedorReporte"
        class="contenedor-reporte oculto">
        <div
            id="reportePaquetes"
            class="reporte-detalle">

            <div class="reporte-header">

                <div>
                    <h2>
                        <i class="fa-solid fa-box"></i>
                        Reporte de paquetes
                    </h2>

                    <p>
                        Consulte el estado de los paquetes registrados.
                    </p>
                </div>

                <button
                    class="btn-cerrar"
                    onclick="cerrarReporte()">

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>

            <div class="filtros">

                <div class="campo">

                    <label>Fecha desde</label>

                    <input
                        type="date"
                        id="fechaPaqueteDesde">

                </div>


                <div class="campo">

                    <label>Fecha hasta</label>

                    <input
                        type="date"
                        id="fechaPaqueteHasta">

                </div>


                <div class="campo">

                    <label>Estado</label>

                    <select id="estadoPaquete">

                        <option value="">Todos</option>
                        <option>Entregado</option>
                        <option>En ruta</option>
                        <option>Pendiente</option>
                        <option>Cancelado</option>

                    </select>

                </div>


                <button
                    class="btn-filtrar"
                    onclick="filtrarTabla('paquetes')">

                    <i class="fa-solid fa-filter"></i>
                    Filtrar

                </button>

            </div>

            <div class="tabla-contenedor">

                <table>

                    <thead>

                        <tr>

                            <th>#</th>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Transportista</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acción</th>

                        </tr>

                    </thead>


                    <tbody id="tablaPaquetes">

                        <tr>

                            <td>1</td>
                            <td>PKG-001</td>
                            <td>María López</td>
                            <td>Luis Torres</td>
                            <td>01/10/2026</td>

                            <td>
                                <span class="badge estado-entregado">
                                    Entregado
                                </span>
                            </td>

                            <td>
                                <button
                                    class="btn-tabla"
                                    onclick="verRegistro('PKG-001')">

                                    Ver

                                </button>
                            </td>

                        </tr>


                        <tr>

                            <td>2</td>
                            <td>PKG-002</td>
                            <td>Juan Pérez</td>
                            <td>Carlos Ruiz</td>
                            <td>01/10/2026</td>

                            <td>
                                <span class="badge estado-ruta">
                                    En ruta
                                </span>
                            </td>

                            <td>
                                <button
                                    class="btn-tabla"
                                    onclick="verRegistro('PKG-002')">

                                    Ver

                                </button>
                            </td>

                        </tr>


                        <tr>

                            <td>3</td>
                            <td>PKG-003</td>
                            <td>Ana Gómez</td>
                            <td>Ana López</td>
                            <td>30/09/2026</td>

                            <td>
                                <span class="badge estado-pendiente">
                                    Pendiente
                                </span>
                            </td>

                            <td>
                                <button
                                    class="btn-tabla"
                                    onclick="verRegistro('PKG-003')">

                                    Ver

                                </button>
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

        <div
            id="reporteIncidencias"
            class="reporte-detalle">

            <div class="reporte-header">

                <div>
                    <h2>
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Reporte de incidencias
                    </h2>

                    <p>
                        Consulte los problemas registrados durante las entregas.
                    </p>
                </div>

                <button
                    class="btn-cerrar"
                    onclick="cerrarReporte()">

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>


            <div class="filtros">

                <div class="campo">

                    <label>Fecha desde</label>

                    <input type="date">

                </div>


                <div class="campo">

                    <label>Fecha hasta</label>

                    <input type="date">

                </div>


                <div class="campo">

                    <label>Tipo de incidencia</label>

                    <select>

                        <option>Todos</option>
                        <option>Paquete dañado</option>
                        <option>Dirección incorrecta</option>
                        <option>Cliente ausente</option>
                        <option>Vehículo</option>
                        <option>Retraso</option>
                        <option>Otro</option>

                    </select>

                </div>


                <button
                    class="btn-filtrar"
                    onclick="filtrarTabla('incidencias')">

                    <i class="fa-solid fa-filter"></i>
                    Filtrar

                </button>

            </div>


            <div class="tabla-contenedor">

                <table>

                    <thead>

                        <tr>

                            <th>Código</th>
                            <th>Incidencia</th>
                            <th>Paquete</th>
                            <th>Transportista</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Fecha</th>

                        </tr>

                    </thead>


                    <tbody>

                        <tr>

                            <td>INC-001</td>
                            <td>Vehículo averiado</td>
                            <td>PKG-025</td>
                            <td>Luis Torres</td>

                            <td>
                                <span class="badge prioridad-alta">
                                    Alta
                                </span>
                            </td>

                            <td>
                                <span class="badge estado-pendiente">
                                    Pendiente
                                </span>
                            </td>

                            <td>01/10/2026</td>

                        </tr>


                        <tr>

                            <td>INC-002</td>
                            <td>Cliente ausente</td>
                            <td>PKG-031</td>
                            <td>Carlos Ruiz</td>

                            <td>
                                <span class="badge prioridad-media">
                                    Media
                                </span>
                            </td>

                            <td>
                                <span class="badge estado-revision">
                                    En revisión
                                </span>
                            </td>

                            <td>01/10/2026</td>

                        </tr>


                        <tr>

                            <td>INC-003</td>
                            <td>Paquete dañado</td>
                            <td>PKG-018</td>
                            <td>Ana López</td>

                            <td>
                                <span class="badge prioridad-alta">
                                    Alta
                                </span>
                            </td>

                            <td>
                                <span class="badge estado-resuelta">
                                    Resuelta
                                </span>
                            </td>

                            <td>30/09/2026</td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

        <div
            id="reporteEntregas"
            class="reporte-detalle">

            <div class="reporte-header">

                <div>
                    <h2>
                        <i class="fa-solid fa-truck"></i>
                        Reporte de entregas
                    </h2>

                    <p>
                        Consulte el estado de las entregas realizadas.
                    </p>
                </div>

                <button
                    class="btn-cerrar"
                    onclick="cerrarReporte()">

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>

            <div class="resumen-entregas">

                <div>
                    <strong>50</strong>
                    <span>Programadas</span>
                </div>

                <div>
                    <strong>38</strong>
                    <span>Completadas</span>
                </div>

                <div>
                    <strong>8</strong>
                    <span>En ruta</span>
                </div>

                <div>
                    <strong>4</strong>
                    <span>Pendientes</span>
                </div>

            </div>

            <div class="filtros">

                <div class="campo">

                    <label>Fecha</label>

                    <input type="date">

                </div>


                <div class="campo">

                    <label>Transportista</label>

                    <select>

                        <option>Todos</option>
                        <option>Luis Torres</option>
                        <option>Carlos Ruiz</option>
                        <option>Ana López</option>

                    </select>

                </div>


                <div class="campo">

                    <label>Estado</label>

                    <select>

                        <option>Todos</option>
                        <option>Entregado</option>
                        <option>En ruta</option>
                        <option>Pendiente</option>

                    </select>

                </div>


                <button
                    class="btn-filtrar"
                    onclick="filtrarTabla('entregas')">

                    <i class="fa-solid fa-filter"></i>
                    Filtrar

                </button>

            </div>

            <div class="tabla-contenedor">

                <table>

                    <thead>

                        <tr>

                            <th>Paquete</th>
                            <th>Cliente</th>
                            <th>Transportista</th>
                            <th>Ruta</th>
                            <th>Fecha</th>
                            <th>Estado</th>

                        </tr>

                    </thead>


                    <tbody>

                        <tr>

                            <td>PKG-001</td>
                            <td>María López</td>
                            <td>Luis Torres</td>
                            <td>Jinotepe</td>
                            <td>01/10/2026</td>

                            <td>
                                <span class="badge estado-entregado">
                                    Entregado
                                </span>
                            </td>

                        </tr>


                        <tr>

                            <td>PKG-002</td>
                            <td>Juan Pérez</td>
                            <td>Carlos Ruiz</td>
                            <td>Diriamba</td>
                            <td>01/10/2026</td>

                            <td>
                                <span class="badge estado-ruta">
                                    En ruta
                                </span>
                            </td>

                        </tr>


                        <tr>

                            <td>PKG-003</td>
                            <td>Ana Gómez</td>
                            <td>Ana López</td>
                            <td>San Marcos</td>
                            <td>01/10/2026</td>

                            <td>
                                <span class="badge estado-pendiente">
                                    Pendiente
                                </span>
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </section>

    <div
        id="botonesExportacion"
        class="exportacion oculto">

        <button onclick="exportarPDF()">
            <i class="fa-solid fa-file-pdf"></i>
            Exportar PDF
        </button>

        <button onclick="exportarExcel()">
            <i class="fa-solid fa-file-excel"></i>
            Exportar Excel
        </button>

        <button onclick="window.print()">
            <i class="fa-solid fa-print"></i>
            Imprimir
        </button>

    </div>

</main>
<script src="js/incidencias.js"></script>

</body>
</html>