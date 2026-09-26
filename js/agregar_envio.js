
document.addEventListener("DOMContentLoaded", function () {

    const clienteSelect =
        document.getElementById("cliente_id");

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

        .then(clientes => {

            clienteSelect.innerHTML =
                '<option value="">Seleccione un cliente...</option>';

            clientes.forEach(cliente => {

                const option =
                    document.createElement("option");

                option.value = cliente.id;

                option.textContent =
                    cliente.nombre +
                    " - " +
                    cliente.cedula;

                clienteSelect.appendChild(option);

            });

        })

        .catch(error => {

            console.error(error);

            clienteSelect.innerHTML =
                '<option value="">Error al cargar clientes</option>';

        });


    /* =====================================
       CARGAR REPARTIDORES
    ===================================== */

    fetch("obtener_repartidores.php")

        .then(response => response.json())

        .then(repartidores => {

            repartidorSelect.innerHTML =
                '<option value="">Seleccione un repartidor...</option>';

            repartidores.forEach(repartidor => {

                const option =
                    document.createElement("option");

                option.value = repartidor.id;

                option.textContent =
                    repartidor.nombre +
                    " - " +
                    repartidor.codigo;

                option.dataset.vehiculo =
                    repartidor.vehiculo || "";

                repartidorSelect.appendChild(option);

            });

        })

        .catch(error => {

            console.error(error);

            repartidorSelect.innerHTML =
                '<option value="">Error al cargar repartidores</option>';

        });


    /* =====================================
       CUANDO CAMBIA EL REPARTIDOR
    ===================================== */

    repartidorSelect.addEventListener(
        "change",
        function () {

            const opcion =
                this.options[this.selectedIndex];

            if (opcion && opcion.dataset.vehiculo) {

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
                this.options[this.selectedIndex];

            const departamentoId =
                opcion.dataset.id;

            municipioSelect.innerHTML =
                '<option value="">Cargando municipios...</option>';

            municipioSelect.disabled = true;


            if (!departamentoId) {

                municipioSelect.innerHTML =
                    '<option value="">Seleccione primero el departamento</option>';

                return;
            }


            fetch(
                "obtener_municipios.php?departamento_id=" +
                departamentoId
            )

            .then(response => response.json())

            .then(municipios => {

                municipioSelect.innerHTML =
                    '<option value="">Seleccione municipio...</option>';

                municipios.forEach(municipio => {

                    const option =
                        document.createElement("option");

                    option.value =
                        municipio.nombre;

                    option.textContent =
                        municipio.nombre;

                    option.dataset.id =
                        municipio.id;

                    municipioSelect.appendChild(option);

                });

                municipioSelect.disabled = false;

            })

            .catch(error => {

                console.error(error);

                municipioSelect.innerHTML =
                    '<option value="">Error al cargar municipios</option>';

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
            municipioId +
            "&peso=" +
            peso
        )

        .then(response => response.json())

        .then(data => {

            if (data.success) {

                mostrarPrecio(
                    parseFloat(data.precio)
                );

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


    function mostrarPrecio(precio) {

        costoInput.value = precio;

        totalInput.value = precio;

        costoMostrar.textContent =
            "C$ " + precio.toFixed(2);

        totalMostrar.textContent =
            "C$ " + precio.toFixed(2);

    }


    pesoInput.addEventListener(
        "input",
        calcularTarifa
    );


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


            if (
                parseFloat(costoInput.value) <= 0
            ) {

                alert(
                    "No se pudo calcular el costo del envío."
                );

                return;
            }


            const datos =
                new FormData(form);


            const boton =
                form.querySelector(".btn-guardar");


            boton.disabled = true;

            boton.textContent =
                "Guardando...";


            fetch("guardar_envio.php", {

                method: "POST",

                body: datos

            })

            .then(response => response.json())

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

});

