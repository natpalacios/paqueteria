
const formulario = document.getElementById("entregaForm");

const mensaje = document.getElementById("mensaje");

const canvas = document.getElementById("firmaCanvas");

const ctx = canvas.getContext("2d");

let dibujando = false;

function posicion(evento) {

    const rect = canvas.getBoundingClientRect();

    const punto = evento.touches
        ? evento.touches[0]
        : evento;

    return {

        x: (punto.clientX - rect.left)
            * canvas.width / rect.width,

        y: (punto.clientY - rect.top)
            * canvas.height / rect.height

    };

}


function iniciarFirma(evento) {

    dibujando = true;

    const punto = posicion(evento);

    ctx.beginPath();

    ctx.moveTo(punto.x, punto.y);

    evento.preventDefault();

}


function dibujarFirma(evento) {

    if (!dibujando) return;

    const punto = posicion(evento);

    ctx.lineTo(punto.x, punto.y);

    ctx.strokeStyle = "#111";

    ctx.lineWidth = 2;

    ctx.lineCap = "round";

    ctx.stroke();

    evento.preventDefault();

}


function terminarFirma() {

    dibujando = false;

}


// EVENTOS DE FIRMA

canvas.addEventListener("mousedown", iniciarFirma);

canvas.addEventListener("mousemove", dibujarFirma);

canvas.addEventListener("mouseup", terminarFirma);

canvas.addEventListener("mouseleave", terminarFirma);

canvas.addEventListener("touchstart", iniciarFirma);

canvas.addEventListener("touchmove", dibujarFirma);

canvas.addEventListener("touchend", terminarFirma);


// BORRAR FIRMA

document.getElementById("borrarFirma")
    .addEventListener("click", function() {

        ctx.clearRect(
            0,
            0,
            canvas.width,
            canvas.height
        );

    });


// ============================================
// PESTAÑAS DE EVIDENCIA
// ============================================

document.querySelectorAll(".pestana")
    .forEach(function(boton) {

        boton.addEventListener("click", function() {

            document.querySelectorAll(".pestana")
                .forEach(function(b) {

                    b.classList.remove("activa");

                });

            document.querySelectorAll(".vista-tab")
                .forEach(function(vista) {

                    vista.classList.remove("activa");

                });

            boton.classList.add("activa");

            const idVista = boton.dataset.tab === "foto"
                ? "vistaFoto"
                : "vistaEstadoFoto";

            document.getElementById(idVista)
                .classList.add("activa");

        });

    });


// ============================================
// VISTA PREVIA DE FOTOGRAFÍA
// ============================================

document.getElementById("fotoPaquete")
    .addEventListener("change", function() {

        const archivo = this.files[0];

        const imagen = document.getElementById("previewImagen");

        if (archivo) {

            imagen.src = URL.createObjectURL(archivo);

            imagen.style.display = "block";

        } else {

            imagen.style.display = "none";

        }

    });


// ============================================
// PRESENCIA DEL CLIENTE
// ============================================

document.querySelectorAll(
    'input[name="presenciaCliente"]'
).forEach(function(radio) {

    radio.addEventListener("change", function() {

        document.querySelectorAll(".opcion-radio")
            .forEach(function(opcion) {

                opcion.classList.remove("seleccionado");

            });

        radio.closest(".opcion-radio")
            .classList.add("seleccionado");


        const estadoFinal =
            document.getElementById("estadoFinal");

        if (radio.value === "Cliente Ausente") {

            estadoFinal.value = "No entregado / Incidencia";

        } else {

            estadoFinal.value = "Entregado con éxito";

        }

    });

});


// ============================================
// SELECCIONAR ESTADO FINAL POR COLOR
// ============================================

// Obtener las tarjetas de estados

const estados = document.querySelectorAll(".estado");

// Obtener el campo oculto del estado

const campoEstado = document.getElementById("estadoColor");


// Recorrer los estados

estados.forEach(function(estado) {

    estado.addEventListener("click", function() {


        // Quitar la selección de los demás estados

        estados.forEach(function(item) {

            item.classList.remove("seleccionado");

        });


        // Marcar el estado seleccionado

        estado.classList.add("seleccionado");


        // Obtener el nombre del estado

        const nombreEstado = estado.dataset.estado;


        // Guardar el estado en el campo oculto

        if (campoEstado) {

            campoEstado.value = nombreEstado;

        }


        // Sincronizar con el campo estadoFinal

        const estadoFinal =
            document.getElementById("estadoFinal");

        if (estadoFinal) {

            estadoFinal.value = nombreEstado;

        }


        console.log(
            "Estado seleccionado:",
            nombreEstado
        );

    });

});


// ============================================
// GUARDAR INFORMACIÓN
// ============================================

formulario.addEventListener("submit", async function(evento) {

    evento.preventDefault();


    const datos = new FormData(formulario);


    // Agregar la firma digital

    datos.append(
        "firma",
        canvas.toDataURL("image/png")
    );


    // Agregar el estado de color seleccionado

    if (campoEstado) {

        datos.set(
            "estadoColor",
            campoEstado.value
        );

    }


    try {

        const respuesta = await fetch(
            "guardar_entrega.php",
            {
                method: "POST",
                body: datos
            }
        );


        const resultado = await respuesta.json();


        mostrarMensaje(
            resultado.mensaje ||
            "Entrega guardada correctamente."
        );


    } catch (error) {

        mostrarMensaje(
            "Modo demostración: revise la conexión con PHP."
        );

        console.log(
            "Para guardar en PHP, ejecute el proyecto con XAMPP."
        );

        console.log(
            Object.fromEntries(datos.entries())
        );

    }

});


// ============================================
// MOSTRAR MENSAJE
// ============================================

function mostrarMensaje(texto) {

    mensaje.textContent = texto;

    mensaje.style.display = "block";

    setTimeout(function() {

        mensaje.style.display = "none";

    }, 3500);

}


// ============================================
// LIMPIAR FORMULARIO
// ============================================

function limpiarFormulario() {

    formulario.reset();

    ctx.clearRect(
        0,
        0,
        canvas.width,
        canvas.height
    );


    // Quitar selección de los estados

    estados.forEach(function(estado) {

        estado.classList.remove("seleccionado");

    });


    // Limpiar el estado seleccionado

    if (campoEstado) {

        campoEstado.value = "";

    }

}
