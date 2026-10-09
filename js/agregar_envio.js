document.addEventListener("DOMContentLoaded", function () {

    const clienteBusqueda =
        document.getElementById("cliente_busqueda");

    const clienteIdInput =
        document.getElementById("cliente_id");

    const listaClientes =
        document.getElementById("lista_clientes");

    const repartidorSelect =
        document.getElementById("repartidor_id");

    const departamentoSelect =
        document.getElementById("departamento");

    const municipioSelect =
        document.getElementById("municipio");

    const pesoInput =
        document.getElementById("peso");

    const costoInput =
        document.getElementById("costo_envio");

    const totalInput =
        document.getElementById("total");

    const costoMostrar =
        document.getElementById("costoMostrar");

    const totalMostrar =
        document.getElementById("totalMostrar");

    const vehiculoInput =
        document.getElementById("responsable_vehiculo");

    const fechaEnvio =
        document.getElementById("fecha_envio");


    let clientes = [];

    let repartidores = [];


    /* =====================================
       FECHA ACTUAL
    ===================================== */

    const hoy = new Date();

    const fecha =
        hoy.getFullYear() +
        "-" +
        String(hoy.getMonth() + 1).padStart(2, "0") +
        "-" +
        String(hoy.getDate()).padStart(2, "0");

    fechaEnvio.value = fecha;


    /* =====================================
       CARGAR CLIENTES
    ===================================== */

    fetch("obtener_clientes.php")

        .then(response => response.json())

        .then(data => {

            clientes = data;

        })

        .catch(error => {

            console.error(
                "Error al cargar clientes:",
                error
            );

        });


    /* =====================================
       BUSCAR CLIENTES
    ===================================== */

    clienteBusqueda.addEventListener(
        "input",
        function () {

            const texto =
                this.value
                    .trim()
                    .toLowerCase();


            clienteIdInput.value = "";


            if (texto === "") {

                listaClientes.innerHTML = "";

                return;

            }


            const coincidencias =
                clientes.filter(cliente => {

                    const nombre =
                        (cliente.nombre || "")
                            .toLowerCase();

                    const cedula =
                        (cliente.cedula || "")
                            .toLowerCase();

                    return (
                        nombre.includes(texto) ||
                        cedula.includes(texto)
                    );

                });


            listaClientes.innerHTML = "";


            if (coincidencias.length === 0) {

                listaClientes.innerHTML =
                    '<div class="cliente-no-encontrado">' +
                    'No se encontraron clientes' +
                    '</div>';

                return;

            }


            coincidencias.forEach(cliente => {

                const opcion =
                    document.createElement("div");

                opcion.className =
                    "cliente-opcion";


                opcion.innerHTML =
                    "<strong>" +
                    escapeHtml(cliente.nombre) +
                    "</strong>" +
                    "<br>" +
                    "<small>" +
                    "Cédula: " +
                    escapeHtml(cliente.cedula || "Sin cédula") +
                    " | Tel: " +
                    escapeHtml(cliente.telefono || "Sin teléfono") +
                    "</small>";


                opcion.addEventListener(
                    "click",
                    function () {

                        clienteBusqueda.value =
                            cliente.nombre;

                        clienteIdInput.value =
                            cliente.id;

                        listaClientes.innerHTML = "";

                    }
                );


                listaClientes.appendChild(opcion);

            });

        }
    );


    /* =====================================
       OCULTAR LISTA AL HACER CLIC FUERA
    ===================================== */

    document.addEventListener(
        "click",
        function (event) {

            if (
                !clienteBusqueda.contains(event.target) &&
                !listaClientes.contains(event.target)
            ) {

                listaClientes.innerHTML = "";

            }

        }
    );


    /* =====================================
       CARGAR REPARTIDORES
    ===================================== */

    fetch("obtener_repartidores.php")

        .then(response => response.json())

        .then(data => {

            repartidores = data;

            actualizarRepartidores();

        })

        .catch(error => {

            console.error(
                "Error al cargar repartidores:",
                error
            );

            repartidorSelect.innerHTML =
                '<option value="">' +
                'Error al cargar repartidores' +
                '</option>';

        });


    /* =====================================
       MOSTRAR REPARTIDORES SEGÚN DEPARTAMENTO
    ===================================== */

    function actualizarRepartidores() {

        const opcionDepartamento =
            departamentoSelect.options[
                departamentoSelect.selectedIndex
            ];


        const departamento =
            opcionDepartamento
                ? opcionDepartamento.value
                : "";


        repartidorSelect.innerHTML =
            '<option value="">' +
            'Seleccione un repartidor...' +
            '</option>';


        vehiculoInput.value = "";


        if (!departamento) {

            return;

        }


        const repartidoresFiltrados =
            repartidores.filter(repartidor => {

                return (
                    String(repartidor.departamento)
                        .trim()
                        .toLowerCase() ===
                    String(departamento)
                        .trim()
                        .toLowerCase()
                );

            });


        if (
            repartidoresFiltrados.length === 0
        ) {

            repartidorSelect.innerHTML =
                '<option value="">' +
                'No hay repartidores activos para este departamento' +
                '</option>';

            return;

        }


        repartidoresFiltrados.forEach(
            repartidor => {

                const option =
                    document.createElement("option");

                option.value =
                    repartidor.id;

                option.textContent =
                    repartidor.nombre +
                    " - " +
                    repartidor.codigo;

                option.dataset.vehiculo =
                    repartidor.vehiculo || "";

                repartidorSelect.appendChild(
                    option
                );

            }
        );

    }


    /* =====================================
       CUANDO CAMBIA EL REPARTIDOR
    ===================================== */

    repartidorSelect.addEventListener(
        "change",
        function () {

            const opcion =
                this.options[
                    this.selectedIndex
                ];


            if (
                opcion &&
                opcion.dataset.vehiculo
            ) {

                vehiculoInput.value =
                    opcion.dataset.vehiculo;

            } else {

                vehiculoInput.value = "";

            }

        }
    );


    /* =====================================
       DEPARTAMENTO → MUNICIPIOS
    ===================================== */

    departamentoSelect.addEventListener(
        "change",
        function () {

            const opcion =
                this.options[
                    this.selectedIndex
                ];


            const departamentoId =
                opcion.dataset.id;


            municipioSelect.innerHTML =
                '<option value="">' +
                'Cargando municipios...' +
                '</option>';

            municipioSelect.disabled = true;


            actualizarRepartidores();


            if (!departamentoId) {

                municipioSelect.innerHTML =
                    '<option value="">' +
                    'Seleccione primero el departamento' +
                    '</option>';

                mostrarPrecio(0);

                return;

            }


            fetch(
                "obtener_municipios.php?departamento_id=" +
                departamentoId
            )

            .then(response => response.json())

            .then(municipios => {

                municipioSelect.innerHTML =
                    '<option value="">' +
                    'Seleccione municipio...' +
                    '</option>';


                municipios.forEach(
                    municipio => {

                        const option =
                            document.createElement(
                                "option"
                            );

                        option.value =
                            municipio.nombre;

                        option.textContent =
                            municipio.nombre;

                        option.dataset.id =
                            municipio.id;

                        municipioSelect.appendChild(
                            option
                        );

                    }
                );


                municipioSelect.disabled = false;

                mostrarPrecio(0);

            })

            .catch(error => {

                console.error(error);

                municipioSelect.innerHTML =
                    '<option value="">' +
                    'Error al cargar municipios' +
                    '</option>';

            });

        }
    );


    /* =====================================
       CALCULAR TARIFA
    ===================================== */

    function calcularTarifa() {

        const peso =
            parseFloat(pesoInput.value);


        const opcion =
            municipioSelect.options[
                municipioSelect.selectedIndex
            ];


        if (
            !peso ||
            peso <= 0 ||
            !opcion ||
            !opcion.dataset.id
        ) {

            mostrarPrecio(0);

            return;

        }


        const municipioId =
            opcion.dataset.id;


        fetch(
            "calcular_tarifa.php?" +
            "municipio_id=" +
            municipioId
        )

        .then(response => response.json())

        .then(data => {

            if (data.success) {

                const costoEnvio =
                    parseFloat(data.precio) || 0;


                const precioKg = 100;


                const costoPeso =
                    peso * precioKg;


                const total =
                    costoEnvio +
                    costoPeso;


                costoInput.value =
                    costoEnvio.toFixed(2);


                totalInput.value =
                    total.toFixed(2);


                costoMostrar.textContent =
                    "C$ " +
                    costoEnvio.toFixed(2);


                totalMostrar.textContent =
                    "C$ " +
                    total.toFixed(2);

            } else {

                mostrarPrecio(0);

                console.log(data.mensaje);

            }

        })

        .catch(error => {

            console.error(error);

            mostrarPrecio(0);

        });

    }


    /* =====================================
       MOSTRAR PRECIO
    ===================================== */

    function mostrarPrecio(
        costoEnvio
    ) {

        costoInput.value =
            costoEnvio.toFixed(2);


        totalInput.value =
            costoEnvio.toFixed(2);


        costoMostrar.textContent =
            "C$ " +
            costoEnvio.toFixed(2);


        totalMostrar.textContent =
            "C$ " +
            costoEnvio.toFixed(2);

    }


    /* =====================================
       CAMBIO DE PESO
    ===================================== */

    pesoInput.addEventListener(
        "input",
        calcularTarifa
    );


    /* =====================================
       CAMBIO DE MUNICIPIO
    ===================================== */

    municipioSelect.addEventListener(
        "change",
        calcularTarifa
    );


    /* =====================================
       GUARDAR ENVÍO
    ===================================== */

    const form =
        document.getElementById("formEnvio");


    form.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();


            /* -----------------------------
               VALIDAR CLIENTE
            ----------------------------- */

            if (
                !clienteIdInput.value
            ) {

                alert(
                    "Debe seleccionar un cliente de la lista."
                );

                clienteBusqueda.focus();

                return;

            }


            /* -----------------------------
               VALIDAR REPARTIDOR
            ----------------------------- */

            if (
                !repartidorSelect.value
            ) {

                alert(
                    "Debe seleccionar un repartidor."
                );

                return;

            }


            /* -----------------------------
               VALIDAR COSTO
            ----------------------------- */

            if (
                parseFloat(
                    costoInput.value
                ) <= 0
            ) {

                alert(
                    "No se pudo calcular el costo del envío."
                );

                return;

            }


            const datos =
                new FormData(form);


            const boton =
                form.querySelector(
                    ".btn-guardar"
                );


            boton.disabled = true;

            boton.textContent =
                "Guardando...";


            fetch(
                "guardar_envio.php",
                {
                    method: "POST",
                    body: datos
                }
            )

            .then(
                response =>
                    response.json()
            )

            .then(data => {

                if (data.success) {

                    alert(
                        "✅ Envío registrado correctamente\n\n" +
                        "Código: " +
                        data.codigo
                    );


                    window.location.href =
                        "agregar_envio.php";

                } else {

                    alert(
                        "❌ " +
                        data.mensaje
                    );

                }

            })

            .catch(error => {

                console.error(error);

                alert(
                    "❌ Ocurrió un error al guardar el envío."
                );

            })

            .finally(() => {

                boton.disabled = false;

                boton.textContent =
                    "💾 Guardar envío";

            });

        }
    );


    /* =====================================
       SEGURIDAD PARA TEXTO HTML
    ===================================== */

    function escapeHtml(text) {

        const div =
            document.createElement("div");

        div.textContent =
            text;

        return div.innerHTML;

    }

});