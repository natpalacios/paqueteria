<?php

require_once "conexion.php";

$sql = "SELECT id, nombre
        FROM departamentos
        ORDER BY nombre ASC";

$resultado = $conexion->query($sql);

$departamentos = [];

while ($fila = $resultado->fetch_assoc()) {
    $departamentos[] = $fila;
}

$conexion->close();

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Nuevo envío - Grupo S.A Logistic </title>

    <link
        rel="stylesheet"
        href="css/agregar_envio.css"
    >

</head>

<body>


<div class="contenedor">


    <div class="encabezado">

        <div>

            <span class="etiqueta">
                Grupo S.A Logistic
            </span>

            <h1>
                Agregar nuevo envío
            </h1>

            <p>
                Registra la información del paquete.
            </p>

        </div>

        <div class="icono-caja">
            📦
        </div>

    </div>


    <form id="formEnvio">


        <!-- INFORMACIÓN DEL ENVÍO -->

        <section class="seccion">

            <h2>📦 Información del envío</h2>


            <div class="grid">


                <div class="campo">

                    <label>
                        Cliente *
                    </label>

                    <select
                        id="cliente_id"
                        name="cliente_id"
                       required
                    >

                        <option value="">
                            Cargando clientes...
                        </option>

                    </select>

                </div>


                <div class="campo">

                    <label>
                        Repartidor *
                    </label>

                    <select
                        id="repartidor_id"
                        name="repartidor_id"
                        required
                    >

                        <option value="">
                            Cargando repartidores...
                        </option>

                    </select>

                </div>


                <div class="campo">

                    <label>
                        Tipo de paquete *
                    </label>

                    <select
                        name="tipo"
                        required
                    >

                        <option value="">
                            Seleccione...
                        </option>

                        <option value="Documento">
                            Documento
                        </option>

                        <option value="Paquete">
                            Paquete
                        </option>

                        <option value="Sobre">
                            Sobre
                        </option>

                        <option value="Caja">
                            Caja
                        </option>

                        <option value="Electrónico">
                            Electrónico
                        </option>

                        <option value="Frágil">
                            Frágil
                        </option>

                    </select>

                </div>


                <div class="campo">

                    <label>
                        Peso (kg) *
                    </label>

                    <input
                        type="number"
                        id="peso"
                        name="peso"
                        min="0.1"
                        step="0.01"
                        placeholder="Ej. 2.5"
                        required
                    >

                </div>


                <div class="campo campo-completo">

                    <label>
                        Descripción
                    </label>

                    <textarea
                        name="descripcion"
                        rows="3"
                        placeholder="Describe el contenido del paquete..."
                    ></textarea>

                </div>


            </div>

        </section>


        <!-- DESTINO -->

        <section class="seccion">

            <h2>📍 Destino del paquete</h2>


            <div class="grid">


                <div class="campo">

                    <label>
                        Departamento *
                    </label>

                    <select
                        id="departamento"
                        name="departamento"
                        required
                    >

                        <option value="">
                            Seleccione departamento...
                        </option>

                        <?php foreach ($departamentos as $dep): ?>

                            <option
                                value="<?= htmlspecialchars($dep['nombre']) ?>"
                                data-id="<?= $dep['id'] ?>"
                            >

                                <?= htmlspecialchars($dep['nombre']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="campo">

                    <label>
                        Municipio *
                    </label>

                    <select
                        id="municipio"
                        name="municipio"
                        required
                        disabled
                    >

                        <option value="">
                            Seleccione primero el departamento
                        </option>

                    </select>

                </div>


                <div class="campo campo-completo">

                    <label>
                        Dirección exacta *
                    </label>

                    <textarea
                        name="direccion"
                        rows="3"
                        placeholder="Dirección donde se entregará el paquete..."
                        required
                    ></textarea>

                </div>


            </div>

        </section>


        <!-- RESPONSABLE -->

        <section class="seccion">

            <h2>👤 Responsable de recibir</h2>


            <div class="grid">


                <div class="campo">

                    <label>
                        Nombre completo *
                    </label>

                    <input
                        type="text"
                        name="responsable_nombre"
                        placeholder="Nombre de quien recibe"
                        required
                    >

                </div>


                <div class="campo">

                    <label>
                        Código / identificación
                    </label>

                    <input
                        type="text"
                        name="responsable_codigo"
                        placeholder="Ej. CED-001"
                    >

                </div>


                <div class="campo">

                    <label>
                        Vehículo responsable
                    </label>

                    <input
                        type="text"
                        name="responsable_vehiculo"
                        id="responsable_vehiculo"
                        placeholder="Vehículo"
                    >

                </div>


            </div>

        </section>


        <!-- FECHAS Y ESTADO -->

        <section class="seccion">

            <h2>📅 Información de entrega</h2>


            <div class="grid">


                <div class="campo">

                    <label>
                        Fecha de envío *
                    </label>

                    <input
                        type="date"
                        name="fecha_envio"
                        id="fecha_envio"
                        required
                    >

                </div>


                <div class="campo">

                    <label>
                        Fecha de entrega
                    </label>

                    <input
                        type="date"
                        name="fecha_entrega"
                    >

                </div>


                <div class="campo">

                    <label>
                        Estado
                    </label>

                    <select name="estado">

                        <option value="Pendiente">
                            Pendiente
                        </option>

                        <option value="En tránsito">
                            En tránsito
                        </option>

                        <option value="Entregado">
                            Entregado
                        </option>

                        <option value="Cancelado">
                            Cancelado
                        </option>

                    </select>

                </div>


            </div>

        </section>


        <!-- PRECIO -->

        <section class="precio-box">

            <div>

                <span>
                    Costo de envío
                </span>

                <strong id="costoMostrar">
                    C$ 0.00
                </strong>

            </div>


            <div>

                <span>
                    Total
                </span>

                <strong id="totalMostrar">
                    C$ 0.00
                </strong>

            </div>


            <input
                type="hidden"
                name="costo_envio"
                id="costo_envio"
                value="0"
            >

            <input
                type="hidden"
                name="total"
                id="total"
                value="0"
            >

        </section>


        <!-- BOTONES -->

        <div class="acciones">

            <button
                type="button"
                class="btn-cancelar"
                onclick="history.back()"
            >
                Cancelar
            </button>


            <button
                type="submit"
                class="btn-guardar"
            >
                💾 Guardar envío
            </button>

        </div>


    </form>


</div>


<script src="js/agregar_envio.js"></script>

</body>

</html>

