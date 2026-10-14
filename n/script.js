
document.addEventListener("DOMContentLoaded", () => {
    const formulario = document.getElementById("entregaForm");
    const canvas = document.getElementById("firmaCanvas");
    const borrarFirma = document.getElementById("borrarFirma");
    const archivoFoto = document.getElementById("fotoPaquete");
    const previewImagen = document.getElementById("previewImagen");
    const mensaje = document.getElementById("mensaje");
    const campoViaje = document.getElementById("textoViaje");
    const estadoFinal = document.getElementById("estadoFinal");

    if (!formulario || !canvas) return;

    const ctx = canvas.getContext("2d");
    let dibujando = false;
    let firmaTrazada = false;

    ctx.lineWidth = 2;
    ctx.lineCap = "round";
    ctx.lineJoin = "round";
    ctx.strokeStyle = "#202020";

    function posicion(evento) {
        const rect = canvas.getBoundingClientRect();
        const punto = evento.touches ? evento.touches[0] : evento;

        return {
            x: (punto.clientX - rect.left) * canvas.width / rect.width,
            y: (punto.clientY - rect.top) * canvas.height / rect.height
        };
    }

    function comenzarDibujo(evento) {
        evento.preventDefault();
        dibujando = true;
        const punto = posicion(evento);
        ctx.beginPath();
        ctx.moveTo(punto.x, punto.y);
    }

    function dibujar(evento) {
        if (!dibujando) return;

        evento.preventDefault();
        const punto = posicion(evento);
        ctx.lineTo(punto.x, punto.y);
        ctx.stroke();
        firmaTrazada = true;
    }

    function terminarDibujo() {
        dibujando = false;
        ctx.beginPath();
    }

    canvas.addEventListener("mousedown", comenzarDibujo);
    canvas.addEventListener("mousemove", dibujar);
    canvas.addEventListener("mouseup", terminarDibujo);
    canvas.addEventListener("mouseleave", terminarDibujo);

    canvas.addEventListener("touchstart", comenzarDibujo, { passive: false });
    canvas.addEventListener("touchmove", dibujar, { passive: false });
    canvas.addEventListener("touchend", terminarDibujo);

    borrarFirma?.addEventListener("click", () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        firmaTrazada = false;
    });

    archivoFoto?.addEventListener("change", () => {
        const archivo = archivoFoto.files[0];

        if (!archivo) {
            previewImagen.style.display = "none";
            previewImagen.removeAttribute("src");
            return;
        }

        if (!["image/jpeg", "image/png", "image/webp"].includes(archivo.type)) {
            archivoFoto.value = "";
            previewImagen.style.display = "none";
            mostrarMensaje("Selecciona una imagen JPG, PNG o WEBP.");
            return;
        }

        if (archivo.size > 5 * 1024 * 1024) {
            archivoFoto.value = "";
            previewImagen.style.display = "none";
            mostrarMensaje("La fotografía no debe superar los 5 MB.");
            return;
        }

        previewImagen.src = URL.createObjectURL(archivo);
        previewImagen.style.display = "block";
    });

    document.querySelectorAll('input[name="presenciaCliente"]').forEach(radio => {
        radio.addEventListener("change", () => {
            if (radio.checked && radio.value === "Cliente Ausente") {
                estadoFinal.value = "No entregado / Incidencia";
            } else if (radio.checked && radio.value === "Entregado con éxito") {
                estadoFinal.value = "Entregado con éxito";
            }
        });
    });

    formulario.addEventListener("reset", () => {
        setTimeout(() => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            firmaTrazada = false;
            previewImagen.style.display = "none";
            previewImagen.removeAttribute("src");
            mostrarMensaje("");
        }, 0);
    });

    formulario.addEventListener("submit", async evento => {
        evento.preventDefault();

        const botonGuardar = document.getElementById("guardarEntrega");
        botonGuardar.disabled = true;
        botonGuardar.textContent = "Guardando...";

        try {
            const datos = new FormData(formulario);

            datos.set("viaje", campoViaje?.value.trim() || "");

            // Si se dibujó una firma, enviar el PNG.
            // Si no, enviar una cadena vacía.
            datos.set(
                "firma",
                firmaTrazada ? canvas.toDataURL("image/png") : ""
            );

            const respuesta = await fetch("guardar_entrega.php", {
                method: "POST",
                body: datos,
                credentials: "same-origin"
            });

            const resultado = await respuesta.json();

            if (!respuesta.ok || !resultado.exito) {
                throw new Error(resultado.mensaje || "No se pudo guardar.");
            }

            mostrarMensaje(resultado.mensaje);

            // Después de guardar, volver a la ruta del transportista.
            // Puedes quitar esta redirección si prefieres permanecer aquí.
            setTimeout(() => {
                window.location.href = "../rutad.php";
            }, 1200);

        } catch (error) {
            console.error(error);
            mostrarMensaje(error.message || "Error de conexión con el servidor.");
        } finally {
            botonGuardar.disabled = false;
            botonGuardar.textContent = "Guardar comprobante";
        }
    });

    function mostrarMensaje(texto) {
        if (!mensaje) return;
        mensaje.textContent = texto;
        mensaje.style.display = texto ? "block" : "none";
        mensaje.style.padding = texto ? "10px" : "0";
        mensaje.style.marginBottom = texto ? "12px" : "0";
        mensaje.style.borderRadius = "6px";
        mensaje.style.background = texto ? "#e8f2ff" : "transparent";
        mensaje.style.color = "#174477";
    }
});
