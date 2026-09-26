<?php
require_once "conexion.php";
require_once "config.php";
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Supervisor - Monitoreo de Envíos</title>

    <link rel="stylesheet"
          href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <link rel="stylesheet" href="css/monitoreo.css">

</head>

<body>

<div class="supervisor-container">

    <header class="supervisor-header">

        <div>
            <h1>Monitoreo de Envíos</h1>

            <p>
                Panel de supervisión y seguimiento de entregas
            </p>
        </div>

       

    </header>


    <main>

        <?php include "monitoreo.php"; ?>

    </main>

</div>


<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

</body>

</html>