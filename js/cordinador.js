document.addEventListener("DOMContentLoaded", function () {

    console.log("JavaScript del Coordinador cargado");


    
    const sidebar = document.getElementById("sidebar");
    const btnMenu = document.getElementById("btnMenu");
    const menuOverlay = document.getElementById("menuOverlay");

    const btnNuevoEnvio = document.getElementById("btnNuevoEnvio");
    const btnClientes = document.getElementById("btnClientes");
    const btnReportes = document.getElementById("btnReportes");

    const modalClientes = new bootstrap.Modal(
        document.getElementById("modalClientes")
    );

    const modalNuevoEnvio = new bootstrap.Modal(
        document.getElementById("modalNuevoEnvio")
    );

    const modalReportes = new bootstrap.Modal(
        document.getElementById("modalReportes")
    );


    
    function abrirMenu() {

        sidebar.classList.add("menu-abierto");

        if (menuOverlay) {
            menuOverlay.classList.add("activo");
        }

    }


    function cerrarMenu() {

        sidebar.classList.remove("menu-abierto");

        if (menuOverlay) {
            menuOverlay.classList.remove("activo");
        }

    }


    if (btnMenu) {

        btnMenu.addEventListener("click", function () {

            if (sidebar.classList.contains("menu-abierto")) {

                cerrarMenu();

            } else {

                abrirMenu();

            }

        });

    }


    if (menuOverlay) {

        menuOverlay.addEventListener(
            "click",
            cerrarMenu
        );

    }


    
    window.cargarDashboard = function () {

        fetch("api.php?accion=dashboard")

            .then(response => response.json())

            .then(data => {

                if (!data.ok) {
                    console.error(data.mensaje);
                    return;
                }

                const datos = data.datos;

                document.getElementById("totalEnvios").textContent =
                    datos.total;

                document.getElementById("enviosTransito").textContent =
                    datos.transito;

                document.getElementById("enviosEntregados").textContent =
                    datos.entregados;

                document.getElementById("enviosPendientes").textContent =
                    datos.pendientes;

            })

            .catch(error => {

                console.error(
                    "Error cargando dashboard:",
                    error
                );

            });

    };


    
    window.cargarEnvios = function () {

        const tabla = document.getElementById("tablaEnvios");

        if (!tabla) return;

        tabla.innerHTML = `
            <tr>
                <td colspan="6" class="text-center">
                    Cargando envíos...
                </td>
            </tr>
        `;


        fetch("api.php?accion=envios")

            .then(response => response.json())

            .then(data => {

                if (!data.ok) {

                    tabla.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center text-danger">
                                ${escapeHTML(data.mensaje)}
                            </td>
                        </tr>
                    `;

                    return;
                }


                if (data.envios.length === 0) {

                    tabla.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center">
                                No hay envíos registrados.
                            </td>
                        </tr>
                    `;

                    return;
                }


                tabla.innerHTML = "";


                data.envios.forEach(envio => {

                    let claseEstado = "";

                    if (envio.estado === "Entregado") {
                        claseEstado = "estado-entregado";
                    }

                    else if (envio.estado === "En tránsito") {
                        claseEstado = "estado-transito";
                    }

                    else {
                        claseEstado = "estado-pendiente";
                    }


                    tabla.innerHTML += `

                        <tr>

                            <td>
                                <strong>
                                    ${escapeHTML(envio.codigo)}
                                </strong>
                            </td>

                            <td>
                                ${escapeHTML(envio.cliente)}
                            </td>

                            <td>
                                ${escapeHTML(envio.repartidor)}
                            </td>

                            <td>
                                <span class="estado-badge ${claseEstado}">
                                    ${escapeHTML(envio.estado)}
                                </span>
                            </td>

                            <td>
                                ${escapeHTML(envio.fecha)}
                            </td>

                            <td>
                                <button
                                    class="btn btn-sm btn-outline-primary"
                                    onclick="verDetalleEnvio(${envio.id})">

                                    <i class="bi bi-eye"></i>

                                </button>
                            </td>

                        </tr>

                    `;

                });

            })

            .catch(error => {

                console.error(error);

                tabla.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-danger">
                            Error conectando con la base de datos.
                        </td>
                    </tr>
                `;

            });

    };


    
    window.verDetalleEnvio = function (id) {

        fetch("api.php?accion=envios")

            .then(response => response.json())

            .then(data => {

                const envio = data.envios.find(
                    e => Number(e.id) === Number(id)
                );

                if (!envio) return;

                alert(
                    "Código: " + envio.codigo +
                    "\nCliente: " + envio.cliente +
                    "\nRepartidor: " + envio.repartidor +
                    "\nOrigen: " + envio.origen +
                    "\nDestino: " + envio.destino +
                    "\nEstado: " + envio.estado +
                    "\nFecha: " + envio.fecha
                );

            });

    };


    
    if (btnClientes) {

        btnClientes.addEventListener("click", function () {

            cargarClientes();

            modalClientes.show();

        });

    }


    function cargarClientes() {

        const tabla = document.getElementById("tablaClientes");

        tabla.innerHTML = `
            <tr>
                <td colspan="5" class="text-center">
                    Cargando clientes...
                </td>
            </tr>
        `;


        fetch("api.php?accion=clientes")

            .then(response => response.json())

            .then(data => {

                if (!data.ok) {

                    tabla.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center text-danger">
                                ${escapeHTML(data.mensaje)}
                            </td>
                        </tr>
                    `;

                    return;
                }


                if (data.clientes.length === 0) {

                    tabla.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center">
                                No hay clientes registrados.
                            </td>
                        </tr>
                    `;

                    return;
                }


                tabla.innerHTML = "";


                data.clientes.forEach(cliente => {

                    tabla.innerHTML += `

                        <tr>

                            <td>
                                ${cliente.id}
                            </td>

                            <td>
                                <strong>
                                    ${escapeHTML(cliente.nombre)}
                                </strong>
                            </td>

                            <td>
                                ${escapeHTML(cliente.telefono || "-")}
                            </td>

                            <td>
                                ${escapeHTML(cliente.correo || "-")}
                            </td>

                            <td>
                                ${escapeHTML(cliente.direccion || "-")}
                            </td>

                        </tr>

                    `;

                });

            })

            .catch(error => {

                console.error(error);

                tabla.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-danger">
                            Error cargando clientes.
                        </td>
                    </tr>
                `;

            });

    }


    
    const btnMostrarFormularioCliente =
        document.getElementById(
            "btnMostrarFormularioCliente"
        );

    const btnCancelarCliente =
        document.getElementById(
            "btnCancelarCliente"
        );

    const formularioCliente =
        document.getElementById(
            "formularioCliente"
        );

    const formCliente =
        document.getElementById(
            "formCliente"
        );


    if (btnMostrarFormularioCliente) {

        btnMostrarFormularioCliente.addEventListener(
            "click",
            function () {

                formularioCliente.style.display = "block";

            }
        );

    }


    if (btnCancelarCliente) {

        btnCancelarCliente.addEventListener(
            "click",
            function () {

                formularioCliente.style.display = "none";

                formCliente.reset();

            }
        );

    }


    if (formCliente) {

        formCliente.addEventListener(
            "submit",
            function (event) {

                event.preventDefault();


                const datos =
                    new FormData(formCliente);

                datos.append(
                    "accion",
                    "crear_cliente"
                );


                fetch("api.php", {

                    method: "POST",

                    body: datos

                })

                .then(response => response.json())

                .then(data => {

                    if (!data.ok) {

                        alert(data.mensaje);

                        return;

                    }


                    alert(
                        "Cliente registrado correctamente."
                    );


                    formCliente.reset();

                    formularioCliente.style.display =
                        "none";


                    cargarClientes();

                    cargarClientesSelect();

                })

                .catch(error => {

                    console.error(error);

                    alert(
                        "Ocurrió un error al guardar el cliente."
                    );

                });

            }
        );

    }


    
    if (btnNuevoEnvio) {

        btnNuevoEnvio.addEventListener(
            "click",
            function () {

                cargarClientesSelect();

                cargarRepartidores();

                modalNuevoEnvio.show();

            }
        );

    }


   
    function cargarClientesSelect() {

        const select =
            document.getElementById(
                "selectCliente"
            );


        fetch("api.php?accion=clientes")

            .then(response => response.json())

            .then(data => {

                if (!data.ok) return;


                select.innerHTML = `
                    <option value="">
                        Seleccione un cliente
                    </option>
                `;


                data.clientes.forEach(cliente => {

                    select.innerHTML += `

                        <option value="${cliente.id}">

                            ${escapeHTML(cliente.nombre)}

                        </option>

                    `;

                });

            });

    }


    
    function cargarRepartidores() {

        const select =
            document.getElementById(
                "selectRepartidor"
            );


        fetch("api.php?accion=repartidores")

            .then(response => response.json())

            .then(data => {

                if (!data.ok) return;


                select.innerHTML = `
                    <option value="">
                        Sin asignar
                    </option>
                `;


                data.repartidores.forEach(repartidor => {

                    select.innerHTML += `

                        <option value="${repartidor.id}">

                            ${escapeHTML(repartidor.nombre)}

                        </option>

                    `;

                });

            });

    }


    // =====================================================
    // GUARDAR ENVÍO
    // =====================================================

    const formEnvio =
        document.getElementById(
            "formEnvio"
        );


    if (formEnvio) {

        formEnvio.addEventListener(
            "submit",
            function (event) {

                event.preventDefault();


                const datos =
                    new FormData(formEnvio);

                datos.append(
                    "accion",
                    "crear_envio"
                );


                fetch("api.php", {

                    method: "POST",

                    body: datos

                })

                .then(response => response.json())

                .then(data => {

                    if (!data.ok) {

                        alert(data.mensaje);

                        return;

                    }


                    alert(
                        "Envío registrado correctamente.\n\nCódigo: "
                        + data.codigo
                        + "\nEstado: Pendiente"
                    );


                    formEnvio.reset();

                    modalNuevoEnvio.hide();


                    cargarDashboard();

                    cargarEnvios();

                })

                .catch(error => {

                    console.error(error);

                    alert(
                        "Ocurrió un error al registrar el envío."
                    );

                });

            }
        );

    }


   
    if (btnReportes) {

        btnReportes.addEventListener(
            "click",
            function () {

                cargarReporte();

                modalReportes.show();

            }
        );

    }


    function cargarReporte() {

        const contenido =
            document.getElementById(
                "contenidoReporte"
            );


        contenido.innerHTML = `
            <div class="text-center p-4">
                Cargando reporte...
            </div>
        `;


        fetch("api.php?accion=reportes")

            .then(response => response.json())

            .then(data => {

                if (!data.ok) {

                    contenido.innerHTML = `
                        <div class="alert alert-danger">
                            ${escapeHTML(data.mensaje)}
                        </div>
                    `;

                    return;

                }


                const r = data.resumen;


                let filas = "";


                data.envios.forEach(envio => {

                    filas += `

                        <tr>

                            <td>
                                ${escapeHTML(envio.codigo)}
                            </td>

                            <td>
                                ${escapeHTML(envio.cliente)}
                            </td>

                            <td>
                                ${escapeHTML(envio.repartidor)}
                            </td>

                            <td>
                                ${escapeHTML(envio.origen)}
                            </td>

                            <td>
                                ${escapeHTML(envio.destino)}
                            </td>

                            <td>
                                ${escapeHTML(envio.estado)}
                            </td>

                            <td>
                                ${escapeHTML(envio.fecha)}
                            </td>

                        </tr>

                    `;

                });


                if (filas === "") {

                    filas = `
                        <tr>
                            <td colspan="7" class="text-center">
                                No hay envíos registrados.
                            </td>
                        </tr>
                    `;

                }


                contenido.innerHTML = `

                    <div class="reporte-print">

                        <div class="reporte-header">

                            <h3>
                                Grupo S.A Logistic
                            </h3>

                            <p>
                                Reporte general de envíos
                            </p>

                            <p>
                                Fecha:
                                ${new Date().toLocaleDateString("es-NI")}
                            </p>

                        </div>


                        <div class="row g-3 mb-4">

                            <div class="col-md-3">

                                <div class="reporte-card">

                                    <small>
                                        Total de envíos
                                    </small>

                                    <strong>
                                        ${r.total}
                                    </strong>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="reporte-card">

                                    <small>
                                        Pendientes
                                    </small>

                                    <strong>
                                        ${r.pendientes}
                                    </strong>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="reporte-card">

                                    <small>
                                        En tránsito
                                    </small>

                                    <strong>
                                        ${r.transito}
                                    </strong>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="reporte-card">

                                    <small>
                                        Entregados
                                    </small>

                                    <strong>
                                        ${r.entregados}
                                    </strong>

                                </div>

                            </div>

                        </div>


                        <div class="table-responsive">

                            <table class="table table-bordered">

                                <thead>

                                    <tr>

                                        <th>Código</th>
                                        <th>Cliente</th>
                                        <th>Repartidor</th>
                                        <th>Origen</th>
                                        <th>Destino</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    ${filas}

                                </tbody>

                            </table>

                        </div>

                    </div>

                `;

            })

            .catch(error => {

                console.error(error);

                contenido.innerHTML = `
                    <div class="alert alert-danger">
                        Error generando el reporte.
                    </div>
                `;

            });

    }


    
    const btnImprimirReporte =
        document.getElementById(
            "btnImprimirReporte"
        );


    if (btnImprimirReporte) {

        btnImprimirReporte.addEventListener(
            "click",
            function () {

                window.print();

            }
        );

    }


    
    function escapeHTML(text) {

        if (text === null || text === undefined) {
            return "";
        }

        return String(text)

            .replace(/&/g, "&amp;")

            .replace(/</g, "&lt;")

            .replace(/>/g, "&gt;")

            .replace(/"/g, "&quot;")

            .replace(/'/g, "&#039;");

    }


    
    cargarDashboard();

    cargarEnvios();

});