
// DATOS DE LAS INCIDENCIAS

const incidencias = {

    1: {
        codigo: "INC-001",
        tipo: "Vehículo averiado",
        paquete: "PKG-025",
        cliente: "María López",
        transportista: "Luis Torres",
        prioridad: "Alta",
        fecha: "01/10/2026 - 15:20",
        ubicacion: "Managua",
        estado: "Pendiente",
        descripcion:
            "El vehículo presentó una falla mecánica durante el traslado del paquete. Se detuvo a mitad de la ruta y se solicitó asistencia."
    },

    2: {
        codigo: "INC-002",
        tipo: "Cliente ausente",
        paquete: "PKG-031",
        cliente: "Juan Pérez",
        transportista: "Carlos Ruiz",
        prioridad: "Media",
        fecha: "01/10/2026 - 11:45",
        ubicacion: "Diriamba",
        estado: "En revisión",
        descripcion:
            "El transportista llegó a la dirección indicada, pero el cliente no se encontraba disponible para recibir el paquete."
    },

    3: {
        codigo: "INC-003",
        tipo: "Paquete dañado",
        paquete: "PKG-018",
        cliente: "Ana Gómez",
        transportista: "Ana López",
        prioridad: "Alta",
        fecha: "30/09/2026 - 16:30",
        ubicacion: "Jinotepe",
        estado: "En proceso",
        descripcion:
            "Se detectó daño en el paquete durante el proceso de entrega."
    },

    4: {
        codigo: "INC-004",
        tipo: "Dirección incorrecta",
        paquete: "PKG-014",
        cliente: "José Pérez",
        transportista: "José Pérez",
        prioridad: "Media",
        fecha: "30/09/2026 - 10:15",
        ubicacion: "San Marcos",
        estado: "Resuelta",
        descripcion:
            "La dirección registrada no coincidía con la ubicación proporcionada por el cliente."
    },

    5: {
        codigo: "INC-005",
        tipo: "Retraso en entrega",
        paquete: "PKG-009",
        cliente: "Carlos Martínez",
        transportista: "Miguel Sánchez",
        prioridad: "Baja",
        fecha: "29/09/2026 - 14:20",
        ubicacion: "Masaya",
        estado: "Resuelta",
        descripcion:
            "La entrega presentó un retraso debido a las condiciones del tráfico."
    },

    6: {
        codigo: "INC-006",
        tipo: "Paquete dañado",
        paquete: "PKG-006",
        cliente: "Laura Rodríguez",
        transportista: "Luis Torres",
        prioridad: "Alta",
        fecha: "28/09/2026 - 09:10",
        ubicacion: "Managua",
        estado: "En revisión",
        descripcion:
            "El paquete presentó daños visibles antes de realizar la entrega."
    }

};


function mostrarDetalle(id) {

    const incidencia = incidencias[id];

    if (!incidencia) {
        return;
    }


    document.getElementById("detalleTipo").textContent =
        incidencia.tipo;

    document.getElementById("detalleCodigo").textContent =
        incidencia.codigo;

    document.getElementById("detallePaquete").textContent =
        incidencia.paquete;

    document.getElementById("detalleCliente").textContent =
        incidencia.cliente;

    document.getElementById("detalleTransportista").textContent =
        incidencia.transportista;

    document.getElementById("detalleFecha").textContent =
        incidencia.fecha;

    document.getElementById("detalleUbicacion").textContent =
        incidencia.ubicacion;

    document.getElementById("detalleDescripcion").textContent =
        incidencia.descripcion;


    const estado = document.getElementById("detalleEstado");

    estado.textContent = incidencia.estado;

    estado.className = "badge";

    if (incidencia.estado === "Pendiente") {
        estado.classList.add("estado-pendiente");
    }

    if (incidencia.estado === "En revisión") {
        estado.classList.add("estado-revision");
    }

    if (incidencia.estado === "En proceso") {
        estado.classList.add("estado-proceso");
    }

    if (incidencia.estado === "Resuelta") {
        estado.classList.add("estado-resuelta");
    }

    document.getElementById("estado").value =
        incidencia.estado;


    document.getElementById("panelDetalle")
        .classList.add("activo");

    document.getElementById("overlay")
        .classList.add("activo");

}



function cerrarDetalle() {

    document.getElementById("panelDetalle")
        .classList.remove("activo");

    document.getElementById("overlay")
        .classList.remove("activo");

}



function guardarCambios() {

    const nuevoEstado =
        document.getElementById("estado").value;

    const observacion =
        document.getElementById("observacion").value;

    alert(
        "Cambios guardados correctamente.\n\n" +
        "Estado: " + nuevoEstado +
        "\nObservación: " +
        (observacion || "Sin observaciones")
    );

    cerrarDetalle();

}



function filtrarIncidencias() {

    const texto =
        document.getElementById("buscar")
        .value
        .toLowerCase();

    const estado =
        document.getElementById("filtroEstado")
        .value;

    const prioridad =
        document.getElementById("filtroPrioridad")
        .value;


    const filas =
        document.querySelectorAll(
            "#tablaIncidencias tr"
        );


    filas.forEach(fila => {

        const contenido =
            fila.textContent.toLowerCase();

        const estadoFila =
            fila.dataset.estado;

        const prioridadFila =
            fila.dataset.prioridad;


        const coincideTexto =
            contenido.includes(texto);

        const coincideEstado =
            estado === "" ||
            estadoFila === estado;

        const coincidePrioridad =
            prioridad === "" ||
            prioridadFila === prioridad;


        if (
            coincideTexto &&
            coincideEstado &&
            coincidePrioridad
        ) {

            fila.style.display = "";

        } else {

            fila.style.display = "none";

        }

    });

}

// MOSTRAR REPORTE

function mostrarReporte(tipo) {

    const contenedor =
        document.getElementById("contenedorReporte");

    const reportePaquetes =
        document.getElementById("reportePaquetes");

    const reporteIncidencias =
        document.getElementById("reporteIncidencias");

    const reporteEntregas =
        document.getElementById("reporteEntregas");

    const botonesExportacion =
        document.getElementById("botonesExportacion");

    reportePaquetes.style.display = "none";
    reporteIncidencias.style.display = "none";
    reporteEntregas.style.display = "none";


    if (tipo === "paquetes") {

        reportePaquetes.style.display = "block";

    }

    if (tipo === "incidencias") {

        reporteIncidencias.style.display = "block";

    }

    if (tipo === "entregas") {

        reporteEntregas.style.display = "block";

    }


    contenedor.classList.remove("oculto");

    botonesExportacion.classList.remove("oculto");


    // Bajar automáticamente hacia el reporte

    contenedor.scrollIntoView({
        behavior: "smooth"
    });

}


function cerrarReporte() {

    const contenedor =
        document.getElementById("contenedorReporte");

    const botonesExportacion =
        document.getElementById("botonesExportacion");


    contenedor.classList.add("oculto");

    botonesExportacion.classList.add("oculto");

}


function verRegistro(codigo) {

    alert(
        "Detalle del paquete: " + codigo
    );

}


function filtrarTabla(tipo) {

    alert(
        "Los filtros del reporte de " +
        tipo +
        " se aplicarán aquí."
    );

}

function exportarPDF() {

    alert(
        "Aquí se generará el reporte en formato PDF."
    );

}



function exportarExcel() {

    alert(
        "Aquí se generará el reporte en formato Excel."
    );

}
const sideMenu = document.getElementById("sideMenu");
const openMenu = document.getElementById("openMenu");
const closeMenu = document.getElementById("closeMenu");

openMenu.addEventListener("click", function () {
    sideMenu.classList.add("activo");
});

closeMenu.addEventListener("click", function () {
    sideMenu.classList.remove("activo");
});