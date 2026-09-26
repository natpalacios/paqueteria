<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Grupo S.A Logistic</title>

    <link rel="stylesheet" href="css/inicioSecion.css">
</head>

<body>

    <div class="login-container">

        <div class="login-box">

            <!-- ICONO DEL CARRITO -->
            <div class="cart-icon">

                <svg viewBox="0 0 100 100">

                    <path d="M20 20 H30 L42 65 H72 L84 35 H52"
                        fill="none"
                        stroke="white"
                        stroke-width="4"
                        stroke-linecap="round"
                        stroke-linejoin="round"/>

                    <line x1="58" y1="48" x2="58" y2="20"
                        stroke="white"
                        stroke-width="4"
                        stroke-linecap="round"/>

                    <polyline points="48,30 58,20 68,30"
                        fill="none"
                        stroke="white"
                        stroke-width="4"
                        stroke-linecap="round"
                        stroke-linejoin="round"/>

                    <circle cx="48" cy="78" r="5"
                        fill="none"
                        stroke="white"
                        stroke-width="3"/>

                    <circle cx="70" cy="78" r="5"
                        fill="none"
                        stroke="white"
                        stroke-width="3"/>

                </svg>

            </div>


            <!-- FORMULARIO DE LOGIN -->
            <form id="frmlogin"
                  action="login.php"
                  method="POST">

                <!-- USUARIO -->
                <div class="input-box">

                    <div class="input-icon">

                        <svg viewBox="0 0 24 24">

                            <circle cx="12" cy="7" r="4"/>

                            <path d="M4 21 C4 16 7 14 12 14 C17 14 20 16 20 21"/>

                        </svg>

                    </div>

                    <input
                        type="text"
                        name="usuario"
                        id="usuario"
                        placeholder="NOMBRE"
                        required>

                </div>


                <!-- CONTRASEÑA -->
                <div class="input-box">

                    <div class="input-icon">

                        <svg viewBox="0 0 24 24">

                            <rect x="5" y="10"
                                  width="14"
                                  height="11"
                                  rx="2"/>

                            <path d="M8 10 V7 C8 4.5 9.8 3 12 3 C14.2 3 16 4.5 16 7 V10"/>

                        </svg>

                    </div>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="CONTRASEÑA"
                        required>

                </div>


                <!-- BOTÓN -->
                <button
                    type="submit"
                    class="btn-inicio">

                    INICIO

                </button>

            </form>

        </div>

    </div>

</body>

</html>

