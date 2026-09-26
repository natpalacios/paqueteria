/* =========================================================
   TRANSPORTISTA
   GRUPO S.A LOGISTIC
========================================================= */

let mapa = null;

let rutaActual = null;
let marcadorOrigen = null;
let marcadorDestino = null;
let marcadorGPS = null;


/* =========================================================
   INICIAR
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    iniciarMapa();

});


/* =========================================================
   INICIAR MAPA
========================================================= */

function iniciarMapa() {

    const elemento = document.getElementById("mapaRuta");

    if (!elemento) {
        console.error("No existe #mapaRuta");
        return;
    }

    mapa = L.map("mapaRuta").setView(
        [11.8496, -86.1990],
        10
    );


    L.tileLayer(
        "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        {
            maxZoom: 19,
            attribution: "&copy; OpenStreetMap contributors"
        }
    ).addTo(mapa);


    /*
        IMPORTANTE:

        Esperamos un momento para que Leaflet
        calcule correctamente el tamaño del mapa.
    */

    setTimeout(function () {

        mapa.invalidateSize();

    }, 500);

}


/* =========================================================
   BUSCAR ENVÍO
========================================================= */

function buscarEnvio(envioId) {

    if (!Array.isArray(envios)) {
        return null;
    }

    return envios.find(function (envio) {

        return Number(envio.id) === Number(envioId);

    });

}


/* =========================================================
   INICIAR ENTREGA
========================================================= */

function iniciarEntrega(envioId) {

    const envio = buscarEnvio(envioId);

    if (!envio) {

        alert("No se encontró el envío.");

        return;
    }


    const confirmar = confirm(
        "¿Deseas iniciar la entrega " +
        envio.codigo +
        "?"
    );


    if (!confirmar) {
        return;
    }


    const boton = document.getElementById(
        "btn-iniciar-" + envioId
    );


    if (boton) {

        boton.disabled = true;

        boton.innerHTML = `
            <span class="spinner-border spinner-border-sm"></span>
            Iniciando...
        `;

    }


    const datos = new FormData();

    datos.append(
        "id",
        envioId
    );


    fetch(
        "rutad.php?accion=iniciar_entrega",
        {
            method: "POST",
            body: datos
        }
    )

    .then(function (respuesta) {

        return respuesta.text();

    })

    .then(function (texto) {

        /*
            Esto nos ayuda a detectar si PHP
            está devolviendo HTML en vez de JSON.
        */

        console.log(
            "Respuesta iniciar entrega:",
            texto
        );


        let resultado;

        try {

            resultado = JSON.parse(texto);

        } catch (error) {

            throw new Error(
                "PHP no devolvió JSON válido."
            );

        }


        if (!resultado.ok) {

            throw new Error(
                resultado.mensaje ||
                "No se pudo iniciar la entrega."
            );

        }


        /*
            Actualizar objeto local
        */

        envio.estado = "En tránsito";

        envio.estadoFiltro = "en-ruta";


        /*
            Cambiar estado visual
        */

        cambiarEstadoVisual(
            envioId,
            "En tránsito"
        );


        /*
            Cambiar botones
        */

        cambiarBotones(
            envioId
        );


        /*
            MOSTRAR RUTA AUTOMÁTICAMENTE
        */

        mostrarEnMapa(
            envioId
        );


    })

    .catch(function (error) {

        console.error(error);

        alert(
            error.message ||
            "No se pudo iniciar la entrega."
        );


        if (boton) {

            boton.disabled = false;

            boton.innerHTML = `
                <i class="bi bi-play-fill"></i>
                Iniciar entrega
            `;

        }

    });

}


/* =========================================================
   CAMBIAR ESTADO VISUAL
========================================================= */

function cambiarEstadoVisual(
    envioId,
    nuevoEstado
) {

    const estado = document.getElementById(
        "estado-" + envioId
    );

    if (!estado) {
        return;
    }


    estado.textContent = nuevoEstado;


    estado.classList.remove(
        "estado-pendiente",
        "estado-ruta",
        "estado-entregado",
        "estado-cancelado"
    );


    estado.classList.add(
        "estado-ruta"
    );

}


/* =========================================================
   CAMBIAR BOTONES
========================================================= */

function cambiarBotones(envioId) {

    const contenedor = document.getElementById(
        "botones-" + envioId
    );

    if (!contenedor) {
        return;
    }


    contenedor.innerHTML = `

        <button
            type="button"
            class="btn btn-primary btn-sm"
            onclick="mostrarEnMapa(${envioId})"
        >

            <i class="bi bi-geo-alt-fill"></i>

            Ver ubicación

        </button>

        <span
            class="badge bg-success-subtle text-success align-self-center"
        >

            <i class="bi bi-check-circle"></i>

            En tránsito

        </span>

    `;

}


/* =========================================================
   MOSTRAR EN MAPA
========================================================= */

function mostrarEnMapa(envioId) {

    const envio = buscarEnvio(envioId);

    if (!envio) {

        alert(
            "No se encontró el envío."
        );

        return;
    }


    if (!mapa) {

        alert(
            "El mapa todavía no está disponible."
        );

        return;
    }


    /*
        Limpiar ruta anterior
    */

    limpiarMapa();


    /*
        IMPORTANTE:

        Si tenemos GPS real registrado,
        mostramos primero el GPS.
    */

    const lat = parseFloat(envio.latitud);
    const lng = parseFloat(envio.longitud);


    if (
        envio.latitud !== null &&
        envio.longitud !== null &&
        !isNaN(lat) &&
        !isNaN(lng) &&
        lat !== 0 &&
        lng !== 0
    ) {

        mostrarGPS(
            envio,
            lat,
            lng
        );

        return;
    }


    /*
        Si no hay GPS todavía,
        calculamos la ruta desde UCN
        hasta la dirección registrada.
    */

    mostrarRuta(
        envio
    );

}


/* =========================================================
   MOSTRAR GPS
========================================================= */

function mostrarGPS(
    envio,
    lat,
    lng
) {

    mapa.setView(
        [lat, lng],
        17,
        {
            animate: true
        }
    );


    marcadorGPS = L.marker(
        [lat, lng]
    ).addTo(mapa);


    marcadorGPS.bindPopup(`

        <div style="min-width:230px;">

            <strong>
                ${escapeHTML(envio.codigo)}
            </strong>

            <br><br>

            <strong>
                Cliente:
            </strong>

            ${escapeHTML(
                envio.cliente_nombre || "Cliente"
            )}

            <br>

            <strong>
                Estado:
            </strong>

            ${escapeHTML(envio.estado)}

            <hr>

            <strong>
                Ubicación GPS actual
            </strong>

            <br>

            Latitud:
            ${lat}

            <br>

            Longitud:
            ${lng}

        </div>

    `);


    marcadorGPS.openPopup();


    actualizarInformacion(
        envio,
        "Ubicación GPS actual"
    );

}


/* =========================================================
   MOSTRAR RUTA
========================================================= */

async function mostrarRuta(envio) {

    const textoMapa =
        document.getElementById(
            "textoMapa"
        );


    if (textoMapa) {

        textoMapa.textContent =
            "Calculando ruta hasta el destino...";

    }


    /*
        Construir parámetros
    */

    const parametros =
        new URLSearchParams({

            municipio:
                envio.municipio || "",

            departamento:
                envio.departamento || "",

            direccion:
                envio.direccion || ""

        });


    try {

        /*
            USAMOS ruta.php

            No hacemos Nominatim directamente
            desde este JavaScript.
        */

        const respuesta = await fetch(
            "ruta.php?" +
            parametros.toString()
        );


        const texto =
            await respuesta.text();


        console.log(
            "Respuesta ruta.php:",
            texto
        );


        let datos;

        try {

            datos = JSON.parse(texto);

        } catch (error) {

            throw new Error(
                "ruta.php no devolvió JSON válido."
            );

        }


        if (!datos.ok) {

            throw new Error(
                datos.mensaje ||
                "No se pudo calcular la ruta."
            );

        }


        /*
            RUTA
        */

        if (datos.geometry) {

            rutaActual =
                L.geoJSON(
                    datos.geometry,
                    {
                        style: {
                            weight: 6,
                            opacity: 0.85
                        }
                    }
                ).addTo(mapa);

        }


        /*
            ORIGEN
        */

        if (
            datos.origen &&
            datos.origen.lat &&
            datos.origen.lon
        ) {

            marcadorOrigen =
                L.marker([
                    parseFloat(datos.origen.lat),
                    parseFloat(datos.origen.lon)
                ]).addTo(mapa);


            marcadorOrigen.bindPopup(`
                <strong>Origen</strong>
                <br>
                Universidad Central de Nicaragua
                <br>
                Jinotepe, Carazo
            `);

        }


        /*
            DESTINO
        */

        if (
            datos.destino &&
            datos.destino.lat &&
            datos.destino.lon
        ) {

            marcadorDestino =
                L.marker([
                    parseFloat(datos.destino.lat),
                    parseFloat(datos.destino.lon)
                ]).addTo(mapa);


            marcadorDestino.bindPopup(`

                <div style="min-width:230px;">

                    <strong>
                        Destino
                    </strong>

                    <br><br>

                    <strong>
                        ${escapeHTML(envio.codigo)}
                    </strong>

                    <br>

                    ${escapeHTML(
                        envio.direccion ||
                        envio.municipio ||
                        "Destino"
                    )}

                    <br>

                    ${escapeHTML(
                        envio.municipio || ""
                    )}

                    ,

                    ${escapeHTML(
                        envio.departamento || ""
                    )}

                </div>

            `);


            marcadorDestino.openPopup();

        }


        /*
            AJUSTAR MAPA
        */

        if (rutaActual) {

            mapa.fitBounds(
                rutaActual.getBounds(),
                {
                    padding: [40, 40]
                }
            );

        } else if (marcadorDestino) {

            mapa.setView(
                marcadorDestino.getLatLng(),
                16
            );

        }


        /*
            INFORMACIÓN
        */

        let distancia = "";

        let tiempo = "";


        if (
            datos.distancia_metros !== undefined
        ) {

            distancia =
                formatearDistancia(
                    datos.distancia_metros
                );

        }


        if (
            datos.duracion_segundos !== undefined
        ) {

            tiempo =
                formatearTiempo(
                    datos.duracion_segundos
                );

        }


        if (textoMapa) {

            textoMapa.innerHTML = `

                <strong>
                    ${escapeHTML(envio.codigo)}
                </strong>

                ·

                ${escapeHTML(envio.estado)}

                ${distancia ? " · " + distancia : ""}

                ${tiempo ? " · " + tiempo : ""}

            `;

        }


        actualizarInformacion(
            envio,
            "Ruta hasta el destino"
        );


    } catch (error) {

        console.error(
            "Error calculando ruta:",
            error
        );


        if (textoMapa) {

            textoMapa.textContent =
                "No fue posible calcular la ruta.";

        }


        alert(
            "No fue posible calcular la ruta.\n\n" +
            error.message
        );

    }

}


/* =========================================================
   LIMPIAR MAPA
========================================================= */

function limpiarMapa() {

    if (!mapa) {
        return;
    }


    if (rutaActual) {

        mapa.removeLayer(
            rutaActual
        );

        rutaActual = null;

    }


    if (marcadorOrigen) {

        mapa.removeLayer(
            marcadorOrigen
        );

        marcadorOrigen = null;

    }


    if (marcadorDestino) {

        mapa.removeLayer(
            marcadorDestino
        );

        marcadorDestino = null;

    }


    if (marcadorGPS) {

        mapa.removeLayer(
            marcadorGPS
        );

        marcadorGPS = null;

    }

}


/* =========================================================
   ACTUALIZAR INFORMACIÓN
========================================================= */

function actualizarInformacion(
    envio,
    texto
) {

    const info =
        document.getElementById(
            "infoUbicacion"
        );


    if (info) {

        info.style.display =
            "block";

    }


    const infoCodigo =
        document.getElementById(
            "infoCodigo"
        );


    const infoCliente =
        document.getElementById(
            "infoCliente"
        );


    const infoDireccion =
        document.getElementById(
            "infoDireccion"
        );


    if (infoCodigo) {

        infoCodigo.textContent =
            envio.codigo;

    }


    if (infoCliente) {

        infoCliente.textContent =
            "Cliente: " +
            (
                envio.cliente_nombre ||
                "Cliente"
            );

    }


    if (infoDireccion) {

        infoDireccion.textContent =
            texto +
            " — " +
            (
                envio.destino ||
                ""
            );

    }

}


/* =========================================================
   DISTANCIA
========================================================= */

function formatearDistancia(metros) {

    metros =
        parseFloat(metros);


    if (isNaN(metros)) {
        return "";
    }


    if (metros < 1000) {

        return (
            Math.round(metros) +
            " m"
        );

    }


    return (
        (metros / 1000)
            .toFixed(1)
            .replace(".", ",")
        +
        " km"
    );

}


/* =========================================================
   TIEMPO
========================================================= */

function formatearTiempo(segundos) {

    segundos =
        parseFloat(segundos);


    if (isNaN(segundos)) {
        return "";
    }


    const minutos =
        Math.round(
            segundos / 60
        );


    if (minutos < 60) {

        return (
            minutos +
            " min"
        );

    }


    const horas =
        Math.floor(
            minutos / 60
        );


    const restantes =
        minutos % 60;


    if (restantes === 0) {

        return (
            horas +
            " h"
        );

    }


    return (
        horas +
        " h " +
        restantes +
        " min"
    );

}


/* =========================================================
   ESCAPAR HTML
========================================================= */

function escapeHTML(texto) {

    if (
        texto === null ||
        texto === undefined
    ) {

        return "";

    }


    return String(texto)

        .replace(
            /&/g,
            "&amp;"
        )

        .replace(
            /</g,
            "&lt;"
        )

        .replace(
            />/g,
            "&gt;"
        )

        .replace(
            /"/g,
            "&quot;"
        )

        .replace(
            /'/g,
            "&#039;"
        );

}