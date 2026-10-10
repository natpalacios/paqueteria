<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de incidencias</title>

    <link rel="stylesheet" href="css/incidencias.css">

  
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
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>

            <div>
                <h1>Gestión de incidencias</h1>
                <p>Revise y gestione las incidencias reportadas durante las entregas.</p>
            </div>
        </section>



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

            <a href="admin_usuarios.php" class="menu-link">
                <span>📍</span>
                <span>Usuario</span>
            </a>

            <a href="clientes.php" class="menu-link">
                <span>📦</span>
                <span>Clientes</span>
            </a>

            <a href="empleados.php" class="menu-link">
                <span>❓</span>
                <span>Empleado</span>
            </a>

            <a href="incidencia.php" class="menu-link">
                <span>👤</span>
                <span>Incidencia</span>
            </a>

            <a href="reporte.php" class="menu-link">
                <span>📊</span>
                <span>Reporte</span>
            </a>

            <a href="logout.php" class="menu-link">
                <span>↪</span>
                <span>Cerrar sesión</span>
            </a>

        </div>

    </div>

</aside>
        <section class="estadisticas">

            <div class="card-estadistica pendiente">
                <div class="icono-estadistica">
                    <i class="fa-solid fa-exclamation"></i>
                </div>

                <div>
                    <strong>4</strong>
                    <span>Pendientes</span>
                </div>
            </div>


            <div class="card-estadistica revision">
                <div class="icono-estadistica">
                    <i class="fa-solid fa-clock"></i>
                </div>

                <div>
                    <strong>3</strong>
                    <span>En revisión</span>
                </div>
            </div>


            <div class="card-estadistica proceso">
                <div class="icono-estadistica">
                    <i class="fa-solid fa-gear"></i>
                </div>

                <div>
                    <strong>2</strong>
                    <span>En proceso</span>
                </div>
            </div>


            <div class="card-estadistica resuelta">
                <div class="icono-estadistica">
                    <i class="fa-solid fa-check"></i>
                </div>

                <div>
                    <strong>15</strong>
                    <span>Resueltas</span>
                </div>
            </div>

        </section>


        <section class="filtros">

            <div class="buscador">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    type="text"
                    id="buscar"
                    placeholder="Buscar por paquete, transportista o tipo de incidencia..."
                >
            </div>

            <select id="filtroEstado">
                <option value="">Todos los estados</option>
                <option value="Pendiente">Pendiente</option>
                <option value="En revisión">En revisión</option>
                <option value="En proceso">En proceso</option>
                <option value="Resuelta">Resuelta</option>
            </select>

            <select id="filtroPrioridad">
                <option value="">Todas las prioridades</option>
                <option value="Alta">Alta</option>
                <option value="Media">Media</option>
                <option value="Baja">Baja</option>
            </select>

            <button class="btn-filtrar" onclick="filtrarIncidencias()">
                <i class="fa-solid fa-filter"></i>
                Filtrar
            </button>

        </section>

        <section class="tabla-contenedor">

            <div class="tabla-titulo">
                <h2>Lista de incidencias</h2>
            </div>

            <div class="tabla-scroll">

                <table>

                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tipo de incidencia</th>
                            <th>Paquete</th>
                            <th>Transportista</th>
                            <th>Prioridad</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody id="tablaIncidencias">

                        <tr
                            data-tipo="Vehículo averiado"
                            data-paquete="PKG-025"
                            data-transportista="Luis Torres"
                            data-prioridad="Alta"
                            data-estado="Pendiente"
                        >
                            <td>1</td>

                            <td>
                                <i class="fa-solid fa-car icono-tabla"></i>
                                Vehículo averiado
                            </td>

                            <td>PKG-025</td>

                            <td>Luis Torres</td>

                            <td>
                                <span class="badge prioridad-alta">Alta</span>
                            </td>

                            <td>
                                01/10/2026<br>
                                <small>15:20</small>
                            </td>

                            <td>
                                <span class="badge estado-pendiente">
                                    Pendiente
                                </span>
                            </td>

                            <td>
                                <button
                                    class="btn-detalle"
                                    onclick="mostrarDetalle(1)"
                                >
                                    Ver detalle
                                </button>
                            </td>
                        </tr>


                        <tr
                            data-tipo="Cliente ausente"
                            data-paquete="PKG-031"
                            data-transportista="Carlos Ruiz"
                            data-prioridad="Media"
                            data-estado="En revisión"
                        >
                            <td>2</td>

                            <td>
                                <i class="fa-solid fa-user icono-tabla"></i>
                                Cliente ausente
                            </td>

                            <td>PKG-031</td>

                            <td>Carlos Ruiz</td>

                            <td>
                                <span class="badge prioridad-media">Media</span>
                            </td>

                            <td>
                                01/10/2026<br>
                                <small>11:45</small>
                            </td>

                            <td>
                                <span class="badge estado-revision">
                                    En revisión
                                </span>
                            </td>

                            <td>
                                <button
                                    class="btn-detalle"
                                    onclick="mostrarDetalle(2)"
                                >
                                    Ver detalle
                                </button>
                            </td>
                        </tr>


                        <tr
                            data-tipo="Paquete dañado"
                            data-paquete="PKG-018"
                            data-transportista="Ana López"
                            data-prioridad="Alta"
                            data-estado="En proceso"
                        >
                            <td>3</td>

                            <td>
                                <i class="fa-solid fa-box icono-tabla"></i>
                                Paquete dañado
                            </td>

                            <td>PKG-018</td>

                            <td>Ana López</td>

                            <td>
                                <span class="badge prioridad-alta">Alta</span>
                            </td>

                            <td>
                                30/09/2026<br>
                                <small>16:30</small>
                            </td>

                            <td>
                                <span class="badge estado-proceso">
                                    En proceso
                                </span>
                            </td>

                            <td>
                                <button
                                    class="btn-detalle"
                                    onclick="mostrarDetalle(3)"
                                >
                                    Ver detalle
                                </button>
                            </td>
                        </tr>


                        <tr
                            data-tipo="Dirección incorrecta"
                            data-paquete="PKG-014"
                            data-transportista="José Pérez"
                            data-prioridad="Media"
                            data-estado="Resuelta"
                        >
                            <td>4</td>

                            <td>
                                <i class="fa-solid fa-location-dot icono-tabla"></i>
                                Dirección incorrecta
                            </td>

                            <td>PKG-014</td>

                            <td>José Pérez</td>

                            <td>
                                <span class="badge prioridad-media">Media</span>
                            </td>

                            <td>
                                30/09/2026<br>
                                <small>10:15</small>
                            </td>

                            <td>
                                <span class="badge estado-resuelta">
                                    Resuelta
                                </span>
                            </td>

                            <td>
                                <button
                                    class="btn-detalle"
                                    onclick="mostrarDetalle(4)"
                                >
                                    Ver detalle
                                </button>
                            </td>
                        </tr>


                        <tr
                            data-tipo="Retraso en entrega"
                            data-paquete="PKG-009"
                            data-transportista="Miguel Sánchez"
                            data-prioridad="Baja"
                            data-estado="Resuelta"
                        >
                            <td>5</td>

                            <td>
                                <i class="fa-solid fa-clock icono-tabla"></i>
                                Retraso en entrega
                            </td>

                            <td>PKG-009</td>

                            <td>Miguel Sánchez</td>

                            <td>
                                <span class="badge prioridad-baja">Baja</span>
                            </td>

                            <td>
                                29/09/2026<br>
                                <small>14:20</small>
                            </td>

                            <td>
                                <span class="badge estado-resuelta">
                                    Resuelta
                                </span>
                            </td>

                            <td>
                                <button
                                    class="btn-detalle"
                                    onclick="mostrarDetalle(5)"
                                >
                                    Ver detalle
                                </button>
                            </td>
                        </tr>


                        <tr
                            data-tipo="Paquete dañado"
                            data-paquete="PKG-006"
                            data-transportista="Luis Torres"
                            data-prioridad="Alta"
                            data-estado="En revisión"
                        >
                            <td>6</td>

                            <td>
                                <i class="fa-solid fa-box icono-tabla"></i>
                                Paquete dañado
                            </td>

                            <td>PKG-006</td>

                            <td>Luis Torres</td>

                            <td>
                                <span class="badge prioridad-alta">Alta</span>
                            </td>

                            <td>
                                28/09/2026<br>
                                <small>09:10</small>
                            </td>

                            <td>
                                <span class="badge estado-revision">
                                    En revisión
                                </span>
                            </td>

                            <td>
                                <button
                                    class="btn-detalle"
                                    onclick="mostrarDetalle(6)"
                                >
                                    Ver detalle
                                </button>
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

            <div class="paginacion">
                <span>Mostrando 1 - 6 de 20 incidencias</span>

                <div>
                    <button>‹</button>
                    <button class="pagina-activa">1</button>
                    <button>2</button>
                    <button>3</button>
                    <button>›</button>
                </div>
            </div>

        </section>

    </main>


    <div id="overlay" class="overlay" onclick="cerrarDetalle()"></div>

    <aside id="panelDetalle" class="panel-detalle">

        <div class="detalle-header">
            <h2>
                <i class="fa-solid fa-triangle-exclamation"></i>
                Detalle de incidencia
            </h2>

            <button class="btn-cerrar" onclick="cerrarDetalle()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>


        <div class="detalle-contenido">

            <div class="detalle-principal">

                <div class="detalle-icono">
                    <i class="fa-solid fa-car"></i>
                </div>

                <div>
                    <h3 id="detalleTipo">Vehículo averiado</h3>
                    <span id="detalleCodigo">INC-001</span>
                </div>

                <span
                    id="detalleEstado"
                    class="badge estado-pendiente"
                >
                    Pendiente
                </span>

            </div>


            <h3 class="subtitulo">Información del reporte</h3>

            <div class="informacion-grid">

                <div>
                    <label>Paquete</label>
                    <strong id="detallePaquete">PKG-025</strong>
                </div>

                <div>
                    <label>Cliente</label>
                    <strong id="detalleCliente">María López</strong>
                </div>

                <div>
                    <label>Transportista</label>
                    <strong id="detalleTransportista">Luis Torres</strong>
                </div>

                <div>
                    <label>Prioridad</label>
                    <strong>
                        <span
                            id="detallePrioridad"
                            class="badge prioridad-alta"
                        >
                            Alta
                        </span>
                    </strong>
                </div>

                <div>
                    <label>Fecha y hora</label>
                    <strong id="detalleFecha">
                        01/10/2026 - 15:20
                    </strong>
                </div>

                <div>
                    <label>Ubicación</label>
                    <strong id="detalleUbicacion">Managua</strong>
                </div>

            </div>


            <!-- DESCRIPCIÓN -->
            <h3 class="subtitulo">Descripción</h3>

            <div class="descripcion">
                <p id="detalleDescripcion">
                    El vehículo presentó una falla mecánica durante
                    el traslado del paquete. Se detuvo a mitad de la
                    ruta y se solicitó asistencia.
                </p>
            </div>


            <h3 class="subtitulo">Evidencia</h3>

            <div class="evidencia">

                <div class="imagen-evidencia">
                    <i class="fa-solid fa-image"></i>
                    <span>Imagen de evidencia</span>
                </div>

                <button class="btn-ver-imagen">
                    <i class="fa-solid fa-image"></i>
                    Ver imagen
                </button>

            </div>

            <div class="gestion">

                <h3 class="subtitulo">Gestión de la incidencia</h3>

                <label for="estado">Estado</label>

                <select id="estado">
                    <option>Pendiente</option>
                    <option>En revisión</option>
                    <option>En proceso</option>
                    <option>Resuelta</option>
                </select>


                <label for="observacion">
                    Observación del administrador
                </label>

                <textarea
                    id="observacion"
                    placeholder="Escriba las acciones realizadas o comentarios..."
                ></textarea>


                <div class="acciones-detalle">

                    <button
                        class="btn-cancelar"
                        onclick="cerrarDetalle()"
                    >
                        Cancelar
                    </button>

                    <button
                        class="btn-guardar"
                        onclick="guardarCambios()"
                    >
                        <i class="fa-solid fa-floppy-disk"></i>
                        Guardar cambios
                    </button>

                </div>

            </div>

        </div>

    </aside>


    <script src="js/incidencias.js"></script>

</body>
</html>