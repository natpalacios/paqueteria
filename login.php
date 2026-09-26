```php
<?php

session_start();

require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$usuario = $_POST["usuario"] ?? "";
$password = $_POST["password"] ?? "";

$sql = "SELECT * FROM usuarios WHERE usuario = ?";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta: " . $conexion->error);
}

$stmt->bind_param("s", $usuario);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {

    $usuarioBD = $resultado->fetch_assoc();

    /*
     * Por ahora mantenemos la misma forma de
     * comprobar la contraseña que ya utilizas.
     */
    if ($password === $usuarioBD["password"]) {

        // Guardar datos del usuario en la sesión
        $_SESSION["id"] = $usuarioBD["id"];
        $_SESSION["nombre"] = $usuarioBD["nombre"];
        $_SESSION["usuario"] = $usuarioBD["usuario"];
        $_SESSION["rol"] = $usuarioBD["rol"];

        // Guardar el ID del repartidor
        $_SESSION["repartidor_id"] = $usuarioBD["repartidor_id"];

        /*
         * REDIRECCIÓN SEGÚN EL ROL
         */

        if ($usuarioBD["rol"] === "Supervisor") {

            header("Location: Supervisor.php");
            exit;

        } elseif ($usuarioBD["rol"] === "Cordinador") {

            header("Location: Cordinador.php");
            exit;

        } elseif ($usuarioBD["rol"] === "Repartidor") {

            /*
             * Verificamos que el usuario realmente
             * tenga un repartidor asociado.
             */
            if (empty($usuarioBD["repartidor_id"])) {
                die("Este usuario no tiene un repartidor asociado.");
            }

            // Entrar a la interfaz de paquetes
            header("Location: paquetes.php");
            exit;

        } else {

            die("El rol del usuario no está configurado correctamente.");

        }

    } else {

        die("Contraseña incorrecta.");

    }

} else {

    die("El usuario no existe.");

}

$stmt->close();
$conexion->close();

?>
```
