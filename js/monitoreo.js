document.addEventListener("DOMContentLoaded", () => {


    /*
    |--------------------------------------------------------------------------
    | MAPA
    |--------------------------------------------------------------------------
    */

    const map =
        L.map("map").setView(
            [11.8496, -86.1990],
            10
        );


    L.tileLayer(
        "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        {

            attribution:
                "&copy; OpenStreetMap contributors"

        }
    ).addTo(map);


    setTimeout(() => {

        map.invalidateSize();

    }, 300);



    /*
    |--------------------------------------------------------------------------
    | VARIABLES DE RUTA
    |--------------------------------------------------------------------------
    */

    let routeLayer = null;

    let originMarker = null;

    let destinationMarker = null;



    /*
    |--------------------------------------------------------------------------
    | ELEMENTOS
    |--------------------------------------------------------------------------
    */

    const routeInfo =
        document.getElementById(
            "routeInfo"
        );


    const clearRoute =
        document.getElementById(
            "clearRoute"
        );



    /*
    |--------------------------------------------------------------------------
    | BUSCAR ENVÍO
    |--------------------------------------------------------------------------
    */

    function buscarEnvio(codigo) {

        return envios.find(
            envio =>
                envio.codigo === codigo
        );

    }



    /*
    |--------------------------------------------------------------------------
    | LIMPIAR RUTA
    |--------------------------------------------------------------------------
    */

    function limpiarRuta() {


        if (routeLayer) {

            map.removeLayer(
                routeLayer
            );

            routeLayer = null;

        }


        if (originMarker) {

            map.removeLayer(
                originMarker
            );

            originMarker = null;

        }


        if (destinationMarker) {

            map.removeLayer(
                destinationMarker
            );

            destinationMarker = null;

        }


        routeInfo.textContent =
            "Seleccione un envío para visualizar su ruta.";

    }



    /*
    |--------------------------------------------------------------------------
    | DISTANCIA
    |--------------------------------------------------------------------------
    */

    function formatearDistancia(metros) {

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
            + " km"
        );

    }



    /*
    |--------------------------------------------------------------------------
    | TIEMPO
    |--------------------------------------------------------------------------
    */

    function formatearTiempo(segundos) {

        const minutos =
            Math.round(
                segundos / 60
            );


        if (minutos < 60) {

            return minutos + " min";

        }


        const horas =
            Math.floor(
                minutos / 60
            );


        const restantes =
            minutos % 60;


        if (restantes === 0) {

            return horas + " h";

        }


        return (
            horas +
            " h " +
            restantes +
            " min"
        );

    }



    /*
    |--------------------------------------------------------------------------
    | MOSTRAR RUTA
    |--------------------------------------------------------------------------
    */

    async function mostrarRuta(codigo) {


        const envio =
            buscarEnvio(codigo);


        if (!envio) {

            alert(
                "No se encontró el envío."
            );

            return;

        }


        limpiarRuta();


        routeInfo.textContent =
            "Buscando dirección y calculando ruta...";


        /*
        |--------------------------------------------------------------------------
        | DATOS
        |--------------------------------------------------------------------------
        */

        const parametros =
            new URLSearchParams({

                municipio:
                    envio.municipio,

                departamento:
                    envio.departamento,

                direccion:
                    envio.direccion || ""

            });



        try {


            const respuesta =
                await fetch(
                    "ruta.php?" +
                    parametros.toString()
                );


            if (!respuesta.ok) {

                throw new Error(
                    "Error HTTP " +
                    respuesta.status
                );

            }


            const datos =
                await respuesta.json();


            if (!datos.ok) {

                throw new Error(
                    datos.mensaje ||
                    "No se pudo calcular la ruta."
                );

            }



            /*
            |--------------------------------------------------------------------------
            | RUTA
            |--------------------------------------------------------------------------
            */

            routeLayer =
                L.geoJSON(
                    datos.geometry,
                    {

                        style: {

                            weight: 6,

                            opacity: 0.85

                        }

                    }
                ).addTo(map);



            /*
            |--------------------------------------------------------------------------
            | ORIGEN
            |--------------------------------------------------------------------------
            */

            originMarker =
                L.marker(
                    [
                        datos.origen.lat,
                        datos.origen.lon
                    ]
                )
                .addTo(map);


            originMarker.bindPopup(
                `
                <strong>Origen</strong>
                <br>
                UCN Jinotepe
                `
            );



            /*
            |--------------------------------------------------------------------------
            | DESTINO
            |--------------------------------------------------------------------------
            */

            destinationMarker =
                L.marker(
                    [
                        datos.destino.lat,
                        datos.destino.lon
                    ]
                )
                .addTo(map);


            destinationMarker.bindPopup(
                `
                <strong>Destino</strong>
                <br>
                ${escapeHtml(
                    envio.direccion ||
                    envio.municipio
                )}
                <br>
                ${escapeHtml(
                    envio.municipio
                )},
                ${escapeHtml(
                    envio.departamento
                )}
                `
            );



            /*
            |--------------------------------------------------------------------------
            | AJUSTAR MAPA
            |--------------------------------------------------------------------------
            */

            map.fitBounds(
                routeLayer.getBounds(),
                {

                    padding: [
                        40,
                        40
                    ]

                }
            );



            /*
            |--------------------------------------------------------------------------
            | INFORMACIÓN
            |--------------------------------------------------------------------------
            */

            routeInfo.innerHTML =

                `<strong>
                    ${escapeHtml(
                        envio.codigo
                    )}
                </strong> ` +

                `· ${escapeHtml(
                    envio.estado
                )} ` +

                `· ${formatearDistancia(
                    datos.distancia_metros
                )} ` +

                `· ${formatearTiempo(
                    datos.duracion_segundos
                )}`;


            destinationMarker.openPopup();


        } catch (error) {


            console.error(error);


            routeInfo.textContent =
                "No fue posible calcular la ruta.";


            alert(
                "No fue posible calcular la ruta.\n\n" +
                error.message
            );

        }

    }



    /*
    |--------------------------------------------------------------------------
    | ESCAPAR HTML
    |--------------------------------------------------------------------------
    */

    function escapeHtml(text) {

        const div =
            document.createElement(
                "div"
            );


        div.textContent =
            text ?? "";


        return div.innerHTML;

    }



    /*
    |--------------------------------------------------------------------------
    | BOTONES VER RUTA
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            ".locate-btn"
        )
        .forEach(btn => {


            btn.addEventListener(
                "click",
                () => {

                    mostrarRuta(
                        btn.dataset.codigo
                    );

                }
            );

        });



    /*
    |--------------------------------------------------------------------------
    | LIMPIAR
    |--------------------------------------------------------------------------
    */

    if (clearRoute) {

        clearRoute.addEventListener(
            "click",
            limpiarRuta
        );

    }



    /*
    |--------------------------------------------------------------------------
    | FILTROS
    |--------------------------------------------------------------------------
    */

    const tabs =
        document.querySelectorAll(
            ".tab"
        );


    const cards =
        document.querySelectorAll(
            ".vehicle-card"
        );


    const vehicleCount =
        document.getElementById(
            "vehicleCount"
        );


    const emptyMessage =
        document.getElementById(
            "emptyMessage"
        );


    function filtrarEnvios(filtro) {


        let visibles = 0;


        cards.forEach(card => {


            const estado =
                card.dataset.status ||
                "";


            if (
                filtro === "todos" ||
                estado === filtro
            ) {

                card.classList.remove(
                    "hidden"
                );

                visibles++;

            } else {

                card.classList.add(
                    "hidden"
                );

            }

        });


        vehicleCount.textContent =
            visibles;


        if (visibles === 0) {

            emptyMessage.classList.add(
                "visible"
            );

        } else {

            emptyMessage.classList.remove(
                "visible"
            );

        }

    }


    tabs.forEach(tab => {


        tab.addEventListener(
            "click",
            () => {


                tabs.forEach(t => {

                    t.classList.remove(
                        "active"
                    );

                });


                tab.classList.add(
                    "active"
                );


                filtrarEnvios(
                    tab.dataset.filter
                );

            }
        );

    });


    filtrarEnvios("todos");



    /*
    |--------------------------------------------------------------------------
    | MENÚ HAMBURGUESA
    |--------------------------------------------------------------------------
    */

    const menuButton =
        document.getElementById(
            "menuButton"
        );


    const sideMenu =
        document.getElementById(
            "sideMenu"
        );


    const closeMenu =
        document.getElementById(
            "closeMenu"
        );


    const menuOverlay =
        document.getElementById(
            "menuOverlay"
        );


    function abrirMenu() {

        sideMenu.classList.add(
            "open"
        );

        menuOverlay.classList.add(
            "visible"
        );

    }


    function cerrarMenu() {

        sideMenu.classList.remove(
            "open"
        );

        menuOverlay.classList.remove(
            "visible"
        );

    }


    if (menuButton) {

        menuButton.addEventListener(
            "click",
            abrirMenu
        );

    }


    if (closeMenu) {

        closeMenu.addEventListener(
            "click",
            cerrarMenu
        );

    }


    if (menuOverlay) {

        menuOverlay.addEventListener(
            "click",
            cerrarMenu
        );

    }



    /*
    |--------------------------------------------------------------------------
    | NOTIFICACIONES
    |--------------------------------------------------------------------------
    */

    const notificationButton =
        document.getElementById(
            "notificationButton"
        );


    const notificationPanel =
        document.getElementById(
            "notificationPanel"
        );


    const notificationBadge =
        document.getElementById(
            "notificationBadge"
        );


    const notificationList =
        document.getElementById(
            "notificationList"
        );


    const markNotificationsRead =
        document.getElementById(
            "markNotificationsRead"
        );


    const openNotificationsFromMenu =
        document.getElementById(
            "openNotificationsFromMenu"
        );



    /*
    |--------------------------------------------------------------------------
    | ÚLTIMO ENVÍO VISTO
    |--------------------------------------------------------------------------
    */

    let ultimoIdVisto =
        parseInt(
            localStorage.getItem(
                "paqueteria_ultimo_envio_visto"
            ) || "0"
        );



    /*
    |--------------------------------------------------------------------------
    | CARGAR NOTIFICACIONES
    |--------------------------------------------------------------------------
    */

    async function cargarNotificaciones() {


        try {


            const respuesta =
                await fetch(
                    "api_supervisor.php?accion=notificaciones&_=" +
                    Date.now()
                );


            const datos =
                await respuesta.json();


            if (!datos.ok) {

                return;

            }


            const nuevas =
                datos.notificaciones.filter(
                    notificacion =>
                        notificacion.id >
                        ultimoIdVisto
                );


            mostrarNotificaciones(
                nuevas
            );


        } catch (error) {

            console.error(
                "Error notificando:",
                error
            );

        }

    }



    /*
    |--------------------------------------------------------------------------
    | MOSTRAR NOTIFICACIONES
    |--------------------------------------------------------------------------
    */

    function mostrarNotificaciones(
        nuevas
    ) {


        if (
            !nuevas ||
            nuevas.length === 0
        ) {

            notificationBadge.textContent =
                "0";

            notificationBadge.classList.remove(
                "visible"
            );


            notificationList.innerHTML = `

                <div class="notification-empty">

                    No hay notificaciones nuevas.

                </div>

            `;

            return;

        }


        notificationBadge.textContent =
            nuevas.length > 9
                ? "9+"
                : nuevas.length;


        notificationBadge.classList.add(
            "visible"
        );


        notificationList.innerHTML =
            "";


        nuevas.forEach(
            notificacion => {


                const item =
                    document.createElement(
                        "div"
                    );


                item.className =
                    "notification-item";


                item.innerHTML = `

                    <div class="notification-item-icon">
                        📦
                    </div>

                    <div class="notification-item-content">

                        <strong>
                            Nuevo envío
                        </strong>

                        <span>
                            ${escapeHtml(
                                notificacion.codigo
                            )}
                        </span>

                        <small>
                            ${escapeHtml(
                                notificacion.municipio
                            )},
                            ${escapeHtml(
                                notificacion.departamento
                            )}
                        </small>

                    </div>

                `;


                item.addEventListener(
                    "click",
                    () => {


                        marcarNotificacionesVistas();


                        const card =
                            document.querySelector(
                                `[data-codigo="${CSS.escape(
                                    notificacion.codigo
                                )}"]`
                            );


                        if (card) {

                            card.scrollIntoView(
                                {
                                    behavior:
                                        "smooth",

                                    block:
                                        "center"
                                }
                            );

                            card.classList.add(
                                "notification-highlight"
                            );


                            setTimeout(
                                () => {

                                    card.classList.remove(
                                        "notification-highlight"
                                    );

                                },
                                2500
                            );

                        }

                    }
                );


                notificationList.appendChild(
                    item
                );

            }
        );

    }



    /*
    |--------------------------------------------------------------------------
    | ABRIR CAMPANITA
    |--------------------------------------------------------------------------
    */

    function abrirNotificaciones() {


        notificationPanel.classList.toggle(
            "open"
        );


        /*
        | Al abrir la campanita:
        | las notificaciones se consideran vistas.
        */

        if (
            notificationPanel.classList.contains(
                "open"
            )
        ) {

            marcarNotificacionesVistas();

        }

    }



    if (notificationButton) {

        notificationButton.addEventListener(
            "click",
            abrirNotificaciones
        );

    }



    /*
    |--------------------------------------------------------------------------
    | MARCAR COMO VISTAS
    |--------------------------------------------------------------------------
    */

    async function marcarNotificacionesVistas() {


        try {


            const respuesta =
                await fetch(
                    "api_supervisor.php?accion=notificaciones&_=" +
                    Date.now()
                );


            const datos =
                await respuesta.json();


            if (
                datos.ok &&
                datos.ultimo_id
            ) {


                ultimoIdVisto =
                    parseInt(
                        datos.ultimo_id
                    );


                localStorage.setItem(
                    "paqueteria_ultimo_envio_visto",
                    ultimoIdVisto
                );


                notificationBadge.textContent =
                    "0";


                notificationBadge.classList.remove(
                    "visible"
                );


                notificationList.innerHTML = `

                    <div class="notification-empty">

                        No hay notificaciones nuevas.

                    </div>

                `;

            }


        } catch (error) {

            console.error(error);

        }

    }



    if (markNotificationsRead) {

        markNotificationsRead.addEventListener(
            "click",
            marcarNotificacionesVistas
        );

    }



    /*
    |--------------------------------------------------------------------------
    | NOTIFICACIONES DESDE EL MENÚ
    |--------------------------------------------------------------------------
    */

    if (openNotificationsFromMenu) {

        openNotificationsFromMenu.addEventListener(
            "click",
            () => {


                cerrarMenu();


                notificationPanel.classList.add(
                    "open"
                );


                cargarNotificaciones();

            }
        );

    }


    document.addEventListener(
        "click",
        event => {


            const wrapper =
                document.querySelector(
                    ".notification-wrapper"
                );


            if (
                wrapper &&
                !wrapper.contains(
                    event.target
                )
            ) {

                notificationPanel.classList.remove(
                    "open"
                );

            }

        }
    );


    cargarNotificaciones();



    setInterval(
        cargarNotificaciones,
        10000
    );

});