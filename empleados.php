
<?php
session_start();
require_once 'conexion.php';

mysqli_set_charset($conexion, 'utf8mb4');

$cargos = [
    'Administrador',
    'Coordinador',
    'Supervisor',
    'Transportista',
    'Repartidor',
    'Administrativo',
    'Atención al cliente'
];

$sucursales = [
    'Managua', 'León', 'Chinandega', 'Estelí',
    'Matagalpa', 'Masaya', 'Granada'
];

$errores = [];
$empleado_editar = null;
$termino = trim($_GET['buscar'] ?? '');

function h($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function empleado_buscar_por_id($conexion, $tipo, $id) {
    if ($tipo === 'repartidor') {
        $stmt = $conexion->prepare(
            "SELECT r.*, 'repartidor' AS tipo
             FROM repartidores r
             WHERE r.id = ?"
        );
        $stmt->bind_param('i', $id);
    } else {
        $stmt = $conexion->prepare(
            "SELECT
                u.id,
                u.nombre,
                u.usuario,
                u.correo,
                u.telefono,
                u.rol,
                u.estado,
                u.repartidor_id,
                r.codigo,
                r.vehiculo,
                r.departamento,
                'usuario' AS tipo
             FROM usuarios u
             LEFT JOIN repartidores r ON r.id = u.repartidor_id
             WHERE u.id = ?"
        );
        $stmt->bind_param('i', $id);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();
    $empleado = $resultado->fetch_assoc();
    $stmt->close();

    return $empleado ?: null;
}

function cargar_empleados($conexion) {
    $empleados = [];

    /*
     * Primero se consultan todos los usuarios.
     * Si tienen un repartidor asociado, sus datos se muestran
     * en el mismo registro para evitar duplicados.
     */
    $sqlUsuarios = "
        SELECT
            u.id,
            u.nombre,
            u.usuario,
            u.correo,
            u.telefono,
            u.rol,
            u.estado,
            u.repartidor_id,
            r.id AS id_repartidor,
            r.codigo,
            r.vehiculo,
            r.departamento,
            'usuario' AS tipo
        FROM usuarios u
        LEFT JOIN repartidores r ON r.id = u.repartidor_id
        ORDER BY u.id DESC
    ";

    $resultado = $conexion->query($sqlUsuarios);

    while ($fila = $resultado->fetch_assoc()) {
        $fila['tipo'] = 'usuario';
        $fila['nombre_completo'] = $fila['nombre'];
        $fila['cargo'] = $fila['rol'];
        $fila['sucursal'] = $fila['departamento'] ?? '';
        $fila['vinculado'] = !empty($fila['id_repartidor']);

        $empleados[] = $fila;
    }

    /*
     * Después se agregan solamente los repartidores
     * que no están asociados a ninguna cuenta de usuario.
     */
    $sqlRepartidores = "
        SELECT
            r.id,
            r.nombre,
            r.codigo,
            r.vehiculo,
            r.departamento,
            r.estado,
            'repartidor' AS tipo
        FROM repartidores r
        WHERE NOT EXISTS (
            SELECT 1
            FROM usuarios u
            WHERE u.repartidor_id = r.id
        )
        ORDER BY r.id DESC
    ";

    $resultado = $conexion->query($sqlRepartidores);

    while ($fila = $resultado->fetch_assoc()) {
        $fila['nombre_completo'] = $fila['nombre'];
        $fila['cargo'] = 'Repartidor';
        $fila['sucursal'] = $fila['departamento'];
        $fila['usuario'] = '';
        $fila['correo'] = '';
        $fila['telefono'] = '';
        $fila['repartidor_id'] = $fila['id'];
        $fila['vinculado'] = false;

        $empleados[] = $fila;
    }

    return $empleados;
}

/* ELIMINAR */
if (isset($_GET['eliminar'], $_GET['tipo'])) {
    $id = (int) $_GET['eliminar'];
    $tipo = $_GET['tipo'];

    if ($id > 0 && $tipo === 'usuario') {
        /*
         * Si el usuario tiene un repartidor vinculado,
         * se elimina la cuenta y también su repartidor asociado.
         */
        $stmt = $conexion->prepare(
            "SELECT repartidor_id FROM usuarios WHERE id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $conexion->prepare(
            "DELETE FROM usuarios WHERE id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        if (!empty($usuario['repartidor_id'])) {
            $idRepartidor = (int) $usuario['repartidor_id'];

            $stmt = $conexion->prepare(
                "DELETE FROM repartidores WHERE id = ?"
            );
            $stmt->bind_param('i', $idRepartidor);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($id > 0 && $tipo === 'repartidor') {
        $stmt = $conexion->prepare(
            "DELETE FROM repartidores WHERE id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }

    $parametros = array_filter([
        'buscar' => $_GET['buscar'] ?? '',
        'pagina' => $_GET['pagina'] ?? ''
    ], fn($v) => $v !== '');

    header('Location: empleados.php?' . http_build_query($parametros));
    exit;
}

/* CARGAR PARA EDITAR */
if (isset($_GET['editar'], $_GET['tipo'])) {
    $tipoEditar = $_GET['tipo'];

    if (in_array($tipoEditar, ['usuario', 'repartidor'], true)) {
        $empleado_editar = empleado_buscar_por_id(
            $conexion,
            $tipoEditar,
            (int) $_GET['editar']
        );
    }
}

/* GUARDAR O ACTUALIZAR */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['accion'] ?? '') === 'guardar'
) {
    $tipo = $_POST['tipo'] ?? 'usuario';
    $id = ($_POST['id'] ?? '') !== ''
        ? (int) $_POST['id']
        : null;

    $idRepartidor = ($_POST['id_repartidor'] ?? '') !== ''
        ? (int) $_POST['id_repartidor']
        : null;

    $nombre = trim($_POST['nombre'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $rol = trim($_POST['rol'] ?? 'Supervisor');
    $codigo = trim($_POST['codigo'] ?? '');
    $vehiculo = trim($_POST['vehiculo'] ?? '');
    $departamento = trim($_POST['departamento'] ?? '');
    $estado = $_POST['estado'] ?? 'Activo';

    if (!in_array($tipo, ['usuario', 'repartidor'], true)) {
        $errores[] = 'El tipo de registro no es válido.';
    }

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    }

    if (!in_array($estado, ['Activo', 'Inactivo'], true)) {
        $errores[] = 'El estado seleccionado no es válido.';
    }

    if ($tipo === 'usuario') {
        if ($usuario === '') {
            $errores[] = 'El nombre de usuario es obligatorio.';
        }

        if ($id === null && $password === '') {
            $errores[] = 'La contraseña es obligatoria para una cuenta nueva.';
        }

        if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo electrónico no es válido.';
        }

        if ($rol === '') {
            $errores[] = 'Debes seleccionar un rol.';
        }

        if ($id === null && $password !== '' && strlen($password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        // Comprobar que el nombre de usuario no esté repetido.
        if ($usuario !== '') {
            $stmt = $conexion->prepare(
                "SELECT id FROM usuarios
                 WHERE usuario = ? AND id <> ?
                 LIMIT 1"
            );
            $idComparar = $id ?? 0;
            $stmt->bind_param('si', $usuario, $idComparar);
            $stmt->execute();
            $repetido = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($repetido) {
                $errores[] = 'Ese nombre de usuario ya existe.';
            }
        }
    } else {
        if ($codigo === '') {
            $errores[] = 'El código del repartidor es obligatorio.';
        }

        if ($vehiculo === '') {
            $errores[] = 'El vehículo es obligatorio.';
        }

        if ($departamento === '') {
            $errores[] = 'El departamento es obligatorio.';
        }

        // Comprobar que el código sea único.
        if ($codigo !== '') {
            $stmt = $conexion->prepare(
                "SELECT id FROM repartidores
                 WHERE codigo = ? AND id <> ?
                 LIMIT 1"
            );
            $idComparar = $id ?? 0;
            $stmt->bind_param('si', $codigo, $idComparar);
            $stmt->execute();
            $repetido = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($repetido) {
                $errores[] = 'Ese código de repartidor ya existe.';
            }
        }
    }

    if (empty($errores)) {
        if ($tipo === 'usuario') {
            if ($id === null) {
                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $conexion->prepare(
                    "INSERT INTO usuarios
                    (nombre, usuario, password, rol, repartidor_id,
                     correo, telefono, estado)
                    VALUES (?, ?, ?, ?, NULL, ?, ?, ?)"
                );

                $stmt->bind_param(
                    'sssssss',
                    $nombre,
                    $usuario,
                    $passwordHash,
                    $rol,
                    $correo,
                    $telefono,
                    $estado
                );

                $stmt->execute();
                $stmt->close();
            } else {
                if ($password !== '' && strlen($password) < 8) {
                    $errores[] = 'La nueva contraseña debe tener al menos 8 caracteres.';
                } else {
                    if ($password !== '') {
                        $passwordHash = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        $stmt = $conexion->prepare(
                            "UPDATE usuarios
                             SET nombre = ?, usuario = ?, password = ?,
                                 rol = ?, correo = ?, telefono = ?, estado = ?
                             WHERE id = ?"
                        );

                        $stmt->bind_param(
                            'sssssssi',
                            $nombre,
                            $usuario,
                            $passwordHash,
                            $rol,
                            $correo,
                            $telefono,
                            $estado,
                            $id
                        );
                    } else {
                        $stmt = $conexion->prepare(
                            "UPDATE usuarios
                             SET nombre = ?, usuario = ?, rol = ?,
                                 correo = ?, telefono = ?, estado = ?
                             WHERE id = ?"
                        );

                        $stmt->bind_param(
                            'ssssssi',
                            $nombre,
                            $usuario,
                            $rol,
                            $correo,
                            $telefono,
                            $estado,
                            $id
                        );
                    }

                    $stmt->execute();
                    $stmt->close();

                    /*
                     * Si este usuario tiene un repartidor asociado,
                     * también se actualizan sus datos de transporte.
                     */
                    if ($idRepartidor !== null) {
                        $stmt = $conexion->prepare(
                            "UPDATE repartidores
                             SET nombre = ?, codigo = ?, vehiculo = ?,
                                 departamento = ?, estado = ?
                             WHERE id = ?"
                        );

                        $stmt->bind_param(
                            'sssssi',
                            $nombre,
                            $codigo,
                            $vehiculo,
                            $departamento,
                            $estado,
                            $idRepartidor
                        );

                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        } else {
            if ($id === null) {
                $stmt = $conexion->prepare(
                    "INSERT INTO repartidores
                    (nombre, codigo, vehiculo, departamento, estado)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    'sssss',
                    $nombre,
                    $codigo,
                    $vehiculo,
                    $departamento,
                    $estado
                );
            } else {
                $stmt = $conexion->prepare(
                    "UPDATE repartidores
                     SET nombre = ?, codigo = ?, vehiculo = ?,
                         departamento = ?, estado = ?
                     WHERE id = ?"
                );

                $stmt->bind_param(
                    'sssssi',
                    $nombre,
                    $codigo,
                    $vehiculo,
                    $departamento,
                    $estado,
                    $id
                );
            }

            $stmt->execute();
            $stmt->close();
        }

        if (empty($errores)) {
            $parametros = array_filter([
                'buscar' => $_GET['buscar'] ?? '',
                'pagina' => $_GET['pagina'] ?? ''
            ], fn($v) => $v !== '');

            header('Location: empleados.php?' . http_build_query($parametros));
            exit;
        }
    }

    $empleado_editar = [
        'id' => $id,
        'id_repartidor' => $idRepartidor,
        'nombre' => $nombre,
        'nombre_completo' => $nombre,
        'usuario' => $usuario,
        'correo' => $correo,
        'telefono' => $telefono,
        'rol' => $rol,
        'cargo' => $rol,
        'codigo' => $codigo,
        'vehiculo' => $vehiculo,
        'departamento' => $departamento,
        'sucursal' => $departamento,
        'estado' => $estado,
        'tipo' => $tipo,
        'password' => ''
    ];
}

/* LISTADO CONJUNTO SIN DUPLICADOS */
$empleados = cargar_empleados($conexion);

if ($termino !== '') {
    $busqueda = mb_strtolower($termino, 'UTF-8');

    $empleados = array_values(array_filter(
        $empleados,
        function ($e) use ($busqueda) {
            $campos = [
                $e['nombre_completo'] ?? '',
                $e['usuario'] ?? '',
                $e['correo'] ?? '',
                $e['telefono'] ?? '',
                $e['cargo'] ?? '',
                $e['codigo'] ?? '',
                $e['vehiculo'] ?? '',
                $e['sucursal'] ?? '',
                $e['estado'] ?? ''
            ];

            foreach ($campos as $campo) {
                if (
                    str_contains(
                        mb_strtolower((string)$campo, 'UTF-8'),
                        $busqueda
                    )
                ) {
                    return true;
                }
            }

            return false;
        }
    ));
}

/* ESTADÍSTICAS */
$total_empleados = count($empleados);

$total_activos = count(array_filter(
    $empleados,
    fn($e) => ($e['estado'] ?? '') === 'Activo'
));

$total_inactivos = $total_empleados - $total_activos;

$cargos_diferentes = count(array_unique(array_filter(
    array_column($empleados, 'cargo')
)));

/* PAGINACIÓN */
$por_pagina = 5;
$total_registros = count($empleados);

$total_paginas = max(
    1,
    (int) ceil($total_registros / $por_pagina)
);

$pagina_actual = max(
    1,
    min($total_paginas, (int)($_GET['pagina'] ?? 1))
);

$empleados_pagina = array_slice(
    $empleados,
    ($pagina_actual - 1) * $por_pagina,
    $por_pagina
);

$cargo_clase = [
    'Administrador' => 'cargo-administrativo',
    'Coordinador' => 'cargo-supervisor',
    'Supervisor' => 'cargo-supervisor',
    'Transportista' => 'cargo-transportista',
    'Repartidor' => 'cargo-repartidor',
    'Administrativo' => 'cargo-administrativo',
    'Atención al cliente' => 'cargo-atencion'
];

$tipoFormulario = $empleado_editar['tipo'] ?? ($_GET['tipo'] ?? 'usuario');

if (!in_array($tipoFormulario, ['usuario', 'repartidor'], true)) {
    $tipoFormulario = 'usuario';
}

$esEdicion = !empty($empleado_editar['id']);
$tieneRepartidor = !empty(
    $empleado_editar['id_repartidor']
) || !empty($empleado_editar['repartidor_id']);

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Empleados · Grupo S.A Logistic</title>
<link rel="stylesheet" href="css/empleado.css">
</head>
<body>

<button type="button" class="menu-btn" id="menuBtn" aria-label="Abrir menú">☰</button>
<div class="menu-fondo" id="menuFondo"></div>

<aside class="side-menu" id="sideMenu">
  <div class="menu-content">
    <div class="menu-header">
      <div>
        <h5>Grupo S.A Logistics</h5>
        <div class="company-subtitle">Paquetería y Envíos</div>
      </div>
      <button type="button" class="menu-close" id="menuClose" aria-label="Cerrar menú">×</button>
    </div>

    <div class="menu-container">
      <a href="admin.php" class="menu-link"><span>🏠</span><span>Inicio</span></a>
      <a href="admin_usuarios.php" class="menu-link"><span>👤</span><span>Usuarios</span></a>
      <a href="clientes.php" class="menu-link"><span>👥</span><span>Clientes</span></a>
      <a href="empleados.php" class="menu-link active"><span>🧑‍💼</span><span>Empleados</span></a>
      <a href="monitoreo.php" class="menu-link"><span>📍</span><span>Monitoreo de envíos</span></a>
      <a href="incidencia.php" class="menu-link"><span>⚠️</span><span>Incidencias</span></a>
      <a href="reporte.php" class="menu-link"><span>📊</span><span>Reportes</span></a>
      <a href="logout.php" class="menu-link"><span>↪</span><span>Cerrar sesión</span></a>
    </div>
  </div>
</aside>

<script>
(function () {
    const menu = document.getElementById('sideMenu');
    const fondo = document.getElementById('menuFondo');

    function abrir() {
        menu.classList.add('activo');
        fondo.classList.add('activo');
    }

    function cerrar() {
        menu.classList.remove('activo');
        fondo.classList.remove('activo');
    }

    document.getElementById('menuBtn').addEventListener('click', abrir);
    document.getElementById('menuClose').addEventListener('click', cerrar);
    fondo.addEventListener('click', cerrar);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') cerrar();
    });
})();
</script>

<main>
  <header class="page-head">
    <span class="page-icon">👨‍👩‍👧</span>
    <div>
      <h1>Empleados</h1>
      <p>Gestiona usuarios y repartidores registrados en el sistema.</p>
    </div>
  </header>

  <section class="stat-cards">
    <div class="stat-card">
      <span class="stat-icono stat-azul">👥</span>
      <div><strong><?= $total_empleados ?></strong><span>Total de empleados</span></div>
    </div>
    <div class="stat-card">
      <span class="stat-icono stat-verde">✓</span>
      <div><strong><?= $total_activos ?></strong><span>Activos</span></div>
    </div>
    <div class="stat-card">
      <span class="stat-icono stat-rojo">✕</span>
      <div><strong><?= $total_inactivos ?></strong><span>Inactivos</span></div>
    </div>
    <div class="stat-card">
      <span class="stat-icono stat-morado">💼</span>
      <div><strong><?= $cargos_diferentes ?></strong><span>Cargos diferentes</span></div>
    </div>
  </section>

  <?php if (!empty($errores)): ?>
    <div class="alerta-error">
      <ul>
        <?php foreach ($errores as $error): ?>
          <li><?= h($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <section class="panel-split" id="form-empleado">
    <div class="panel panel-form">
      <div class="panel-title panel-title-oscuro">
        <span class="plus">⊕</span>
        <?= $esEdicion ? 'Editar empleado' : 'Registrar empleado' ?>
      </div>

      <form method="POST" action="empleados.php<?= $termino !== '' ? '?buscar=' . urlencode($termino) : '' ?>">
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="<?= h($empleado_editar['id'] ?? '') ?>">
        <input type="hidden" name="id_repartidor"
               value="<?= h($empleado_editar['id_repartidor'] ?? $empleado_editar['repartidor_id'] ?? '') ?>">

        <div class="form-grid">
          <div class="campo">
            <label>Tipo de registro *</label>
            <div class="campo-icono">
              <span class="icono">📋</span>
              <select name="tipo" id="tipoRegistro"
                      <?= $esEdicion ? 'disabled' : '' ?> required>
                <option value="usuario" <?= $tipoFormulario === 'usuario' ? 'selected' : '' ?>>Usuario</option>
                <option value="repartidor" <?= $tipoFormulario === 'repartidor' ? 'selected' : '' ?>>Repartidor</option>
              </select>
              <?php if ($esEdicion): ?>
                <input type="hidden" name="tipo" value="<?= h($tipoFormulario) ?>">
              <?php endif; ?>
            </div>
          </div>

          <div class="campo">
            <label>Nombre completo *</label>
            <div class="campo-icono">
              <span class="icono">👤</span>
              <input type="text" name="nombre" placeholder="Nombre completo"
                     value="<?= h($empleado_editar['nombre'] ?? $empleado_editar['nombre_completo'] ?? '') ?>" required>
            </div>
          </div>

          <div class="campo campos-usuario">
            <label>Nombre de usuario *</label>
            <div class="campo-icono">
              <span class="icono">🪪</span>
              <input type="text" name="usuario" placeholder="Ej. ltorres"
                     value="<?= h($empleado_editar['usuario'] ?? '') ?>">
            </div>
          </div>

          <div class="campo campos-usuario">
            <label>Contraseña <?= $esEdicion ? '(opcional)' : '*' ?></label>
            <div class="campo-icono">
              <span class="icono">🔒</span>
              <input type="password" name="password"
                     placeholder="<?= $esEdicion ? 'Dejar en blanco para conservarla' : 'Mínimo 8 caracteres' ?>"
                     autocomplete="new-password">
            </div>
          </div>

          <div class="campo campos-usuario">
            <label>Correo electrónico</label>
            <div class="campo-icono">
              <span class="icono">✉️</span>
              <input type="email" name="correo" placeholder="correo@empresa.com"
                     value="<?= h($empleado_editar['correo'] ?? '') ?>">
            </div>
          </div>

          <div class="campo campos-usuario">
            <label>Teléfono</label>
            <div class="campo-icono">
              <span class="icono">📞</span>
              <input type="text" name="telefono" placeholder="Ej. 8888-8888"
                     value="<?= h($empleado_editar['telefono'] ?? '') ?>">
            </div>
          </div>

          <div class="campo campos-usuario">
            <label>Rol *</label>
            <div class="campo-icono">
              <span class="icono">💼</span>
              <select name="rol">
                <?php foreach ($cargos as $cargo): ?>
                  <option value="<?= h($cargo) ?>"
                    <?= ($empleado_editar['rol'] ?? $empleado_editar['cargo'] ?? 'Supervisor') === $cargo ? 'selected' : '' ?>>
                    <?= h($cargo) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="campo campos-repartidor">
            <label>Código del repartidor *</label>
            <div class="campo-icono">
              <span class="icono">🔢</span>
              <input type="text" name="codigo" placeholder="Ej. REP-001"
                     value="<?= h($empleado_editar['codigo'] ?? '') ?>">
            </div>
          </div>

          <div class="campo campos-repartidor">
            <label>Vehículo *</label>
            <div class="campo-icono">
              <span class="icono">🚚</span>
              <input type="text" name="vehiculo" placeholder="Ej. Moto o camioneta"
                     value="<?= h($empleado_editar['vehiculo'] ?? '') ?>">
            </div>
          </div>

          <div class="campo campos-repartidor">
            <label>Departamento *</label>
            <div class="campo-icono">
              <span class="icono">🏢</span>
              <select name="departamento">
                <option value="">Seleccionar departamento</option>
                <?php foreach ($sucursales as $sucursal): ?>
                  <option value="<?= h($sucursal) ?>"
                    <?= ($empleado_editar['departamento'] ?? $empleado_editar['sucursal'] ?? '') === $sucursal ? 'selected' : '' ?>>
                    <?= h($sucursal) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="campo">
            <label>Estado *</label>
            <div class="campo-icono">
              <span class="icono">✅</span>
              <select name="estado" required>
                <option value="Activo" <?= ($empleado_editar['estado'] ?? 'Activo') === 'Activo' ? 'selected' : '' ?>>Activo</option>
                <option value="Inactivo" <?= ($empleado_editar['estado'] ?? '') === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
              </select>
            </div>
          </div>
        </div>

        <?php if ($esEdicion && $tipoFormulario === 'usuario' && $tieneRepartidor): ?>
          <p class="ayuda-vinculo">
            Este usuario tiene un repartidor vinculado. Al guardar, también se actualizarán los datos del repartidor.
          </p>
        <?php endif; ?>

        <div class="form-acciones">
          <a href="empleados.php" class="btn-outline">↻ Limpiar</a>
          <button type="submit" class="btn-primary">💾 Guardar</button>
        </div>
      </form>
    </div>
  </section>

  <section class="panel">
    <div class="lista-header">
      <span class="lista-icono">👨‍👩‍👧</span>
      <h2>Lista de empleados</h2>

      <form method="GET" action="empleados.php" class="buscador">
        <input type="text" name="buscar"
               placeholder="🔍 Buscar por nombre, usuario, rol, código o departamento..."
               value="<?= h($termino) ?>">
      </form>

      <a href="empleados.php#form-empleado" class="btn-primary">+ Nuevo empleado</a>
    </div>

    <div class="table-scroll">
      <table class="tabla-datos">
        <thead>
          <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Usuario / Código</th>
            <th>Rol / Vehículo</th>
            <th>Teléfono</th>
            <th>Departamento</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>

        <tbody>
          <?php if (empty($empleados_pagina)): ?>
            <tr>
              <td colspan="8" class="sin-resultados">No se encontraron empleados.</td>
            </tr>
          <?php endif; ?>

          <?php foreach ($empleados_pagina as $i => $e): ?>
            <?php
              $esUsuario = $e['tipo'] === 'usuario';
              $idFila = (int)$e['id'];
              $tipoFila = $esUsuario ? 'usuario' : 'repartidor';
              $identificador = $esUsuario
                  ? ($e['usuario'] ?? '')
                  : ($e['codigo'] ?? '');
              $cargoMostrar = $esUsuario
                  ? ($e['rol'] ?? '')
                  : ($e['vehiculo'] ?? 'Repartidor');
              $telefonoMostrar = $esUsuario
                  ? ($e['telefono'] ?? '')
                  : '—';
              $departamentoMostrar = $e['departamento'] ?? '—';
            ?>
            <tr>
              <td data-label="#">
                <?= ($pagina_actual - 1) * $por_pagina + $i + 1 ?>
              </td>

              <td data-label="Nombre">
                <?= h($e['nombre_completo']) ?>
                <?php if (!empty($e['vinculado'])): ?>
                  <small style="display:block;color:#6b7280;">Repartidor vinculado</small>
                <?php endif; ?>
              </td>

              <td data-label="Usuario / Código">
                <?= h($identificador ?: '—') ?>
              </td>

              <td data-label="Rol / Vehículo">
                <span class="badge-cargo <?= $cargo_clase[$e['cargo'] ?? ''] ?? '' ?>">
                  <?= h($cargoMostrar ?: '—') ?>
                </span>
              </td>

              <td data-label="Teléfono"><?= h($telefonoMostrar ?: '—') ?></td>

              <td data-label="Departamento"><?= h($departamentoMostrar ?: '—') ?></td>

              <td data-label="Estado">
                <span class="badge-estado <?= ($e['estado'] ?? '') === 'Activo' ? 'estado-activo' : 'estado-inactivo' ?>">
                  <?= ($e['estado'] ?? '') === 'Activo' ? '✓ ' : '✕ ' ?>
                  <?= h($e['estado'] ?? '') ?>
                </span>
              </td>

              <td data-label="Acciones" class="acciones">
                <a
                  href="empleados.php?editar=<?= $idFila ?>&tipo=<?= h($tipoFila) ?>&buscar=<?= urlencode($termino) ?>#form-empleado"
                  class="btn-icon btn-editar"
                  title="Editar">✎</a>

                <a
                  href="empleados.php?eliminar=<?= $idFila ?>&tipo=<?= h($tipoFila) ?>&buscar=<?= urlencode($termino) ?>&pagina=<?= $pagina_actual ?>"
                  class="btn-icon btn-eliminar"
                  title="Eliminar"
                  onclick="return confirm('¿Deseas eliminar este registro?');">🗑</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="lista-footer">
      <span>
        Mostrando
        <?= $total_registros === 0 ? 0 : ($pagina_actual - 1) * $por_pagina + 1 ?>
        -
        <?= min($pagina_actual * $por_pagina, $total_registros) ?>
        de <?= $total_registros ?> empleados
      </span>

      <div class="paginacion">
        <a class="pag-btn <?= $pagina_actual <= 1 ? 'disabled' : '' ?>"
           href="empleados.php?<?= http_build_query(array_filter(['buscar' => $termino, 'pagina' => max(1, $pagina_actual - 1)])) ?>">‹</a>

        <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
          <a class="pag-btn <?= $p === $pagina_actual ? 'activo' : '' ?>"
             href="empleados.php?<?= http_build_query(array_filter(['buscar' => $termino, 'pagina' => $p])) ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>

        <a class="pag-btn <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>"
           href="empleados.php?<?= http_build_query(array_filter(['buscar' => $termino, 'pagina' => min($total_paginas, $pagina_actual + 1)])) ?>">›</a>
      </div>
    </div>
  </section>
</main>

<script>
(function () {
    const selector = document.getElementById('tipoRegistro');
    const formulario = document.querySelector('#form-empleado form');

    if (!selector || !formulario) return;

    function actualizarCampos() {
        const tipo = selector.value;
        const esUsuario = tipo === 'usuario';

        formulario.querySelectorAll('.campos-usuario').forEach(function (campo) {
            campo.style.display = esUsuario ? '' : 'none';

            campo.querySelectorAll('input, select').forEach(function (control) {
                control.disabled = !esUsuario;
            });
        });

        formulario.querySelectorAll('.campos-repartidor').forEach(function (campo) {
            campo.style.display = esUsuario ? 'none' : '';

            campo.querySelectorAll('input, select').forEach(function (control) {
                control.disabled = esUsuario;
            });
        });

        const inputUsuario = formulario.querySelector('[name="usuario"]');
        const inputPassword = formulario.querySelector('[name="password"]');
        const inputCodigo = formulario.querySelector('[name="codigo"]');
        const inputVehiculo = formulario.querySelector('[name="vehiculo"]');
        const inputDepartamento = formulario.querySelector('[name="departamento"]');

        if (inputUsuario) inputUsuario.required = esUsuario;
        if (inputPassword) {
            inputPassword.required = esUsuario && !formulario.querySelector('[name="id"]').value;
        }
        if (inputCodigo) inputCodigo.required = !esUsuario;
        if (inputVehiculo) inputVehiculo.required = !esUsuario;
        if (inputDepartamento) inputDepartamento.required = !esUsuario;
    }

    selector.addEventListener('change', actualizarCampos);
    actualizarCampos();
})();
</script>

</body>
</html>
