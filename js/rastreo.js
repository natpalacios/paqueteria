document.addEventListener("DOMContentLoaded", () => {

    const estadoGPS = document.getElementById("estadoGPS");
    const velocidadElemento = document.getElementById("velocidad");
    const latitudElemento = document.getElementById("latitud");
    const longitudElemento = document.getElementById("longitud");


    if (!navigator.geolocation) {

        estadoGPS.textContent =
            "Este dispositivo no soporta GPS.";

        return;
    }


    estadoGPS.textContent =
        "Solicitando ubicación...";


    navigator.geolocation.watchPosition(

        async (position) => {

            const latitud =
                position.coords.latitude;

            const longitud =
                position.coords.longitude;


            /*
             * coords.speed viene en metros por segundo.
             *
             * Lo convertimos a km/h.
             */

            let velocidad = null;

            if (
                position.coords.speed !== null &&
                !isNaN(position.coords.speed)
            ) {

                velocidad =
                    position.coords.speed * 3.6;

            }


            const rumbo =
                position.coords.heading;


            latitudElemento.textContent =
                latitud.toFixed(6);

            longitudElemento.textContent =
                longitud.toFixed(6);


            if (velocidad !== null) {

                velocidadElemento.textContent =
                    velocidad.toFixed(1) + " km/h";

            } else {

                velocidadElemento.textContent =
                    "Calculando...";

            }


            estadoGPS.textContent =
                "GPS activo";


            /*
             * Enviamos la posición al servidor.
             */

            const datos = new FormData();

            datos.append(
                "envio_id",
                ENVIO_ID
            );

            datos.append(
                "latitud",
                latitud
            );

            datos.append(
                "longitud",
                longitud
            );

            if (velocidad !== null) {

                datos.append(
                    "velocidad",
                    velocidad
                );

            }

            if (
                rumbo !== null &&
                !isNaN(rumbo)
            ) {

                datos.append(
                    "rumbo",
                    rumbo
                );

            }


            try {

                const respuesta =
                    await fetch(
                        "guardar_posicion.php",
                        {
                            method: "POST",
                            body: datos
                        }
                    );


                const resultado =
                    await respuesta.json();


                if (!resultado.ok) {

                    console.error(
                        resultado.mensaje
                    );

                }

            } catch (error) {

                console.error(
                    "Error enviando GPS:",
                    error
                );

            }

        },

        (error) => {

            console.error(error);

            estadoGPS.textContent =
                "No se pudo obtener la ubicación.";

        },

        {
            enableHighAccuracy: true,
            maximumAge: 3000,
            timeout: 10000
        }

    );

});