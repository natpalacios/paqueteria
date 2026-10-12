
<?php
session_start();

require_once __DIR__ . '/conexion.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$roles = ['Administrador', 'Coordinador', 'Supervisor', 'Transportista'];
$errores = [];
$usuario_editar = null;
$repartidores = [];
$mensaje_error_bd = '';

/* Token de seguridad para formularios */
if (empty($_SESSION['csrf_usuarios'])) {
    $_SESSION['csrf_usuarios'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_usuarios'];

/* Escapar datos antes de mostrarlos en HTML */
function e($valor): string {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/* Redireccionar conservando el buscador */
function volver_a_usuarios($termino = ''): void {
    $parametros = [];
    if ($termino !== '') {
        $parametros['buscar'] = $termino;
    }

    header('Location: admin_usuarios.php' .
        ($parametros ? '?' . http_build_query($parametros) : ''));
    exit;
}

/* Obtener los repartidores disponibles para asignación */
try {
    $resultado_repartidores = $conexion->query(
        "SELECT id, nombre, codigo, vehiculo, departamento, estado
         FROM repartidores
         ORDER BY nombre ASC"
    );

    while ($fila = $resultado_repartidores->fetch_assoc()) {
        $repartidores[] = $fila;
    }
} catch (mysqli_sql_exception $ex) {
    $errores[] = 'No se pudieron consultar los repartidores. Verificá la tabla repartidores.';
}

/* Eliminar un usuario mediante POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['accion'] ?? '') === 'eliminar') {

    $token = $_POST['csrf'] ?? '';
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $termino_eliminar = trim($_POST['buscar'] ?? '');

    if (!is_string($token) || !hash_equals($csrf, $token)) {
        $errores[] = 'La sesión del formulario expiró. Actualizá la página e intentá nuevamente.';
    } elseif (!$id || $id < 1) {
        $errores[] = 'El usuario seleccionado no es válido.';
    } else {
        try {
            $stmt = $conexion->prepare(
                "DELETE FROM usuarios WHERE id = ?"
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();

            volver_a_usuarios($termino_eliminar);
        } catch (mysqli_sql_exception $ex) {
            $errores[] = 'No se pudo eliminar el usuario. Verificá si está relacionado con otros registros.';
        }
    }
}

/* Guardar un usuario nuevo o actualizar uno existente */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['accion'] ?? '') === 'guardar') {

    $token = $_POST['csrf'] ?? '';

    $id_edicion = ($_POST['id'] ?? '') !== ''
        ? filter_var($_POST['id'], FILTER_VALIDATE_INT)
        : null;

    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $nombre_usuario = trim($_POST['usuario'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $rol = $_POST['rol'] ?? '';
    $estado = $_POST['estado'] ?? 'Activo';

    $repartidor_id = ($_POST['repartidor_id'] ?? '') !== ''
        ? filter_var($_POST['repartidor_id'], FILTER_VALIDATE_INT)
        : null;

    $termino_formulario = trim($_POST['buscar'] ?? '');

    if (!is_string($token) || !hash_equals($csrf, $token)) {
        $errores[] = 'La sesión del formulario expiró. Actualizá la página e intentá nuevamente.';
    }

    if ($id_edicion !== null && (!$id_edicion || $id_edicion < 1)) {
        $errores[] = 'El identificador del usuario no es válido.';
        $id_edicion = null;
    }

    if ($nombre_completo === '') {
        $errores[] = 'El nombre completo es obligatorio.';
    } elseif (mb_strlen($nombre_completo) > 100) {
        $errores[] = 'El nombre no puede superar los 100 caracteres.';
    }

    if ($nombre_usuario === '') {
        $errores[] = 'El usuario es obligatorio.';
    } elseif (mb_strlen($nombre_usuario) > 50) {
        $errores[] = 'El nombre de usuario no puede superar los 50 caracteres.';
    }

    if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no es válido.';
    } elseif (mb_strlen($correo) > 100) {
        $errores[] = 'El correo no puede superar los 100 caracteres.';
    }

    if (mb_strlen($telefono) > 20) {
        $errores[] = 'El teléfono no puede superar los 20 caracteres.';
    }

    if (!in_array($rol, $roles, true)) {
        $errores[] = 'Debés seleccionar un rol válido.';
    }

    if (!in_array($estado, ['Activo', 'Inactivo'], true)) {
        $errores[] = 'Debés seleccionar un estado válido.';
    }

    if ($id_edicion === null && strlen($contrasena) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($contrasena !== '' && strlen($contrasena) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    }

    /* Validar la asignación del repartidor */
    if ($rol === 'Transportista') {
        if ($repartidor_id === null || $repartidor_id === false || $repartidor_id < 1) {
            $errores[] = 'Seleccioná un repartidor para el usuario Transportista.';
        } else {
            $repartidor_valido = false;

            foreach ($repartidores as $r) {
                if ((int)$r['id'] === (int)$repartidor_id
                    && $r['estado'] === 'Activo') {
                    $repartidor_valido = true;
                    break;
                }
            }

            if (!$repartidor_valido) {
                $errores[] = 'El repartidor seleccionado no existe o está inactivo.';
            }
        }
    } else {
        /* Los demás roles no requieren repartidor */
        $repartidor_id = null;
    }

    /* Evitar duplicados de usuario o correo */
    if (empty($errores)) {
        try {
            if ($id_edicion === null) {
                $stmt = $conexion->prepare(
                    "SELECT id FROM usuarios
                     WHERE usuario = ? OR correo = ?
                     LIMIT 1"
                );
                $stmt->bind_param('ss', $nombre_usuario, $correo);
            } else {
                $stmt = $conexion->prepare(
                    "SELECT id FROM usuarios
                     WHERE (usuario = ? OR correo = ?) AND id <> ?
                     LIMIT 1"
                );
                $stmt->bind_param('ssi', $nombre_usuario, $correo, $id_edicion);
            }

            $stmt->execute();
            $duplicado = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($duplicado) {
                $errores[] = 'Ya existe otro usuario con ese nombre de usuario o correo.';
            }
        } catch (mysqli_sql_exception $ex) {
            $errores[] = 'No se pudo validar el usuario. Revisá la conexión con la base de datos.';
        }
    }

    /* Insertar o actualizar */
    if (empty($errores)) {
        try {
            $nombre_db = $nombre_completo;
            $usuario_db = $nombre_usuario;
            $rol_db = $rol;
            $correo_db = $correo;
            $telefono_db = $telefono;
            $estado_db = $estado;
            $repartidor_db = $repartidor_id === null
                ? null
                : (int)$repartidor_id;

            if ($id_edicion === null) {
                $password_hash = password_hash($contrasena, PASSWORD_DEFAULT);

                $stmt = $conexion->prepare(
                    "INSERT INTO usuarios
                    (nombre, usuario, password, rol, repartidor_id, correo, telefono, estado)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    'ssssisss',
                    $nombre_db,
                    $usuario_db,
                    $password_hash,
                    $rol_db,
                    $repartidor_db,
                    $correo_db,
                    $telefono_db,
                    $estado_db
                );

                $stmt->execute();
                $stmt->close();
            } else {
                if ($contrasena !== '') {
                    $password_hash = password_hash($contrasena, PASSWORD_DEFAULT);

                    $stmt = $conexion->prepare(
                        "UPDATE usuarios
                         SET nombre = ?, usuario = ?, password = ?,
                             rol = ?, repartidor_id = ?, correo = ?,
                             telefono = ?, estado = ?
                         WHERE id = ?"
                    );

                    $stmt->bind_param(
                        'ssssisssi',
                        $nombre_db,
                        $usuario_db,
                        $password_hash,
                        $rol_db,
                        $repartidor_db,
                        $correo_db,
                        $telefono_db,
                        $estado_db,
                        $id_edicion
                    );
                } else {
                    $stmt = $conexion->prepare(
                        "UPDATE usuarios
                         SET nombre = ?, usuario = ?, rol = ?,
                             repartidor_id = ?, correo = ?,
                             telefono = ?, estado = ?
                         WHERE id = ?"
                    );

                    $stmt->bind_param(
                        'sssisssi',
                        $nombre_db,
                        $usuario_db,
                        $rol_db,
                        $repartidor_db,
                        $correo_db,
                        $telefono_db,
                        $estado_db,
                        $id_edicion
                    );
                }

                $stmt->execute();
                $stmt->close();
            }

            volver_a_usuarios($termino_formulario);

        } catch (mysqli_sql_exception $ex) {
            $errores[] = 'No se pudo guardar el usuario. Verificá los datos y la estructura de la base de datos.';
        }
    }

    /* Mantener los valores del formulario si hay errores */
    if (!empty($errores)) {
        $usuario_editar = [
            'id' => $id_edicion,
            'nombre_completo' => $nombre_completo,
            'usuario' => $nombre_usuario,
            'correo' => $correo,
            'telefono' => $telefono,
            'rol' => $rol,
            'estado' => $estado,
            'repartidor_id' => $repartidor_id,
        ];
    }
}

/* Cargar un usuario para editar */
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['editar'])) {
    $id_buscar = filter_var($_GET['editar'], FILTER_VALIDATE_INT);

    if ($id_buscar && $id_buscar > 0) {
        try {
            $stmt = $conexion->prepare(
                "SELECT id, nombre AS nombre_completo, usuario,
                        correo, telefono, rol, estado, repartidor_id
                 FROM usuarios
                 WHERE id = ?"
            );
            $stmt->bind_param('i', $id_buscar);
            $stmt->execute();

            $usuario_editar = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } catch (mysqli_sql_exception $ex) {
            $errores[] = 'No se pudo cargar el usuario para editar.';
        }
    }
}

/* Buscar y paginar registros desde MySQL */
$termino = trim($_GET['buscar'] ?? '');

$por_pagina = 5;
$pagina_actual = max(1, (int)($_GET['pagina'] ?? 1));
$total_usuarios = 0;
$total_paginas = 1;
$usuarios_pagina = [];

try {
    if ($termino !== '') {
        $busqueda = '%' . $termino . '%';

        $stmt = $conexion->prepare(
            "SELECT COUNT(*) AS total
             FROM usuarios
             WHERE nombre LIKE ? OR usuario LIKE ? OR correo LIKE ?"
        );
        $stmt->bind_param('sss', $busqueda, $busqueda, $busqueda);
    } else {
        $stmt = $conexion->prepare(
            "SELECT COUNT(*) AS total FROM usuarios"
        );
    }

    $stmt->execute();
    $total_usuarios = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $total_paginas = max(1, (int)ceil($total_usuarios / $por_pagina));
    $pagina_actual = min($pagina_actual, $total_paginas);
    $offset = ($pagina_actual - 1) * $por_pagina;

    if ($termino !== '') {
        $busqueda = '%' . $termino . '%';

        $stmt = $conexion->prepare(
            "SELECT id, nombre AS nombre_completo, usuario,
                    correo, telefono, rol, estado, repartidor_id
             FROM usuarios
             WHERE nombre LIKE ? OR usuario LIKE ? OR correo LIKE ?
             ORDER BY id DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->bind_param(
            'sssii',
            $busqueda,
            $busqueda,
            $busqueda,
            $por_pagina,
            $offset
        );
    } else {
        $stmt = $conexion->prepare(
            "SELECT id, nombre AS nombre_completo, usuario,
                    correo, telefono, rol, estado, repartidor_id
             FROM usuarios
             ORDER BY id DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->bind_param('ii', $por_pagina, $offset);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($fila = $resultado->fetch_assoc()) {
        $usuarios_pagina[] = $fila;
    }

    $stmt->close();

} catch (mysqli_sql_exception $ex) {
    $errores[] = 'No se pudieron consultar los usuarios. Revisá la tabla usuarios.';
}

$roles_clase = [
    'Coordinador' => 'rol-coordinador',
    'Supervisor' => 'rol-supervisor',
    'Transportista' => 'rol-transportista',
    'Administrador' => 'rol-administrador',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administración de usuarios · Grupo S.A Logistic</title>
<link rel="stylesheet" href="css/usuario.css">
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
      <a href="admin.php" class="menu-link">
        <span>🏠</span><span>Inicio</span>
      </a>
      <a href="admin_usuarios.php" class="menu-link">
        <span>👤</span><span>Usuarios</span>
      </a>
      <a href="clientes.php" class="menu-link">
        <span>👥</span><span>Clientes</span>
      </a>
      <a href="empleados.php" class="menu-link">
        <span>🧑‍💼</span><span>Empleados</span>
      </a>
      <a href="monitoreo.php" class="menu-link">
        <span>📍</span><span>Monitoreo de envíos</span>
      </a>
      <a href="incidencia.php" class="menu-link">
        <span>⚠️</span><span>Incidencias</span>
      </a>
      <a href="reporte.php" class="menu-link">
        <span>📊</span><span>Reportes</span>
      </a>
      <a href="logout.php" class="menu-link">
        <span>↪</span><span>Cerrar sesión</span>
      </a>
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
    <span class="page-icon">👤</span>
    <div>
      <h1>Administración de usuarios</h1>
      <p>Gestione los usuarios del sistema, asigne roles y mantenga la seguridad de la información.</p>
    </div>
  </header>

  <?php if (!empty($errores)): ?>
    <div class="alerta-error">
      <ul>
        <?php foreach ($errores as $error): ?>
          <li><?= e($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <section class="panel" id="form-usuario">
    <div class="panel-title">
      <span class="plus">⊕</span>
      <?= $usuario_editar && !empty($usuario_editar['id'])
          ? 'Editar usuario'
          : 'Nuevo usuario' ?>
    </div>

    <form method="POST" action="admin_usuarios.php<?= $termino !== '' ? '?buscar=' . urlencode($termino) : '' ?>">
      <input type="hidden" name="accion" value="guardar">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="id" value="<?= e($usuario_editar['id'] ?? '') ?>">
      <input type="hidden" name="buscar" value="<?= e($termino) ?>">

      <div class="form-grid">
        <div class="campo">
          <label>Nombre completo *</label>
          <input type="text" name="nombre_completo"
                 placeholder="Ej. María López"
                 maxlength="100"
                 value="<?= e($usuario_editar['nombre_completo'] ?? '') ?>"
                 required>
        </div>

        <div class="campo">
          <label>Usuario *</label>
          <input type="text" name="usuario"
                 placeholder="Ej. mlopez"
                 maxlength="50"
                 value="<?= e($usuario_editar['usuario'] ?? '') ?>"
                 required>
        </div>

        <div class="campo">
          <label>Teléfono</label>
          <input type="text" name="telefono"
                 placeholder="Ej. 8888-8888"
                 maxlength="20"
                 value="<?= e($usuario_editar['telefono'] ?? '') ?>">
        </div>

        <div class="campo">
          <label>Correo electrónico *</label>
          <input type="email" name="correo"
                 placeholder="ejemplo@correo.com"
                 maxlength="100"
                 value="<?= e($usuario_editar['correo'] ?? '') ?>"
                 required>
        </div>

        <div class="campo">
          <label>
            Contraseña
            <?= ($usuario_editar && !empty($usuario_editar['id'])) ? '' : '*' ?>
          </label>
          <div class="campo-password">
            <input type="password" name="contrasena" id="campoContrasena"
                   autocomplete="new-password"
                   placeholder="<?= ($usuario_editar && !empty($usuario_editar['id']))
                       ? 'Dejar en blanco para no cambiar'
                       : 'Mínimo 8 caracteres' ?>"
                   <?= ($usuario_editar && !empty($usuario_editar['id'])) ? '' : 'required' ?>>
            <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
          </div>
        </div>

        <div class="campo">
          <label>Estado</label>
          <select name="estado">
            <option value="Activo"
              <?= (($usuario_editar['estado'] ?? 'Activo') === 'Activo') ? 'selected' : '' ?>>
              Activo
            </option>
            <option value="Inactivo"
              <?= (($usuario_editar['estado'] ?? '') === 'Inactivo') ? 'selected' : '' ?>>
              Inactivo
            </option>
          </select>
        </div>

        <div class="campo">
          <label>Rol *</label>
          <select name="rol" id="rolUsuario" required>
            <option value="" disabled
              <?= empty($usuario_editar['rol']) ? 'selected' : '' ?>>
              Seleccionar rol
            </option>
            <?php foreach ($roles as $r): ?>
              <option value="<?= e($r) ?>"
                <?= (($usuario_editar['rol'] ?? '') === $r) ? 'selected' : '' ?>>
                <?= e($r) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="campo" id="campoRepartidor" style="display:none;">
          <label>Repartidor asignado *</label>
          <select name="repartidor_id" id="repartidorUsuario">
            <option value="">Seleccionar repartidor</option>
            <?php foreach ($repartidores as $r): ?>
              <?php if ($r['estado'] === 'Activo'
                  || (string)($usuario_editar['repartidor_id'] ?? '') === (string)$r['id']): ?>
                <option value="<?= e($r['id']) ?>"
                  <?= ((string)($usuario_editar['repartidor_id'] ?? '') === (string)$r['id']) ? 'selected' : '' ?>>
                  <?= e($r['nombre']) ?> · <?= e($r['codigo']) ?>
                  — <?= e($r['vehiculo']) ?>
                  <?= $r['estado'] === 'Inactivo' ? ' (Inactivo)' : '' ?>
                </option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
          <?php if (empty($repartidores)): ?>
            <small>No hay repartidores registrados. Primero agregá uno en el módulo de repartidores.</small>
          <?php endif; ?>
        </div>
      </div>

      <div class="form-acciones">
        <a href="admin_usuarios.php<?= $termino !== '' ? '?buscar=' . urlencode($termino) : '' ?>"
           class="btn-outline">↻ Limpiar</a>
        <button type="submit" class="btn-primary">💾 Guardar</button>
      </div>
    </form>
  </section>

  <section class="panel">
    <div class="lista-header">
      <h2>Lista de usuarios</h2>
      <form method="GET" action="admin_usuarios.php" class="buscador">
        <input type="text" name="buscar"
               placeholder="🔍 Buscar por nombre, usuario o correo..."
               value="<?= e($termino) ?>">
      </form>
      <a href="admin_usuarios.php#form-usuario" class="btn-primary">+ Nuevo usuario</a>
    </div>

    <div class="table-scroll">
      <table class="tabla-usuarios">
        <thead>
          <tr>
            <th>#</th>
            <th>Nombre completo</th>
            <th>Usuario</th>
            <th>Correo electrónico</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($usuarios_pagina)): ?>
            <tr>
              <td colspan="7" class="sin-resultados">No se encontraron usuarios.</td>
            </tr>
          <?php endif; ?>

          <?php foreach ($usuarios_pagina as $i => $u): ?>
            <tr>
              <td><?= ($pagina_actual - 1) * $por_pagina + $i + 1 ?></td>
              <td><?= e($u['nombre_completo']) ?></td>
              <td><?= e($u['usuario']) ?></td>
              <td><?= e($u['correo']) ?></td>
              <td>
                <span class="badge-rol <?= e($roles_clase[$u['rol']] ?? '') ?>">
                  <?= e($u['rol']) ?>
                </span>
              </td>
              <td>
                <span class="badge-estado <?= $u['estado'] === 'Activo'
                    ? 'estado-activo'
                    : 'estado-inactivo' ?>">
                  <?= e($u['estado']) ?>
                </span>
              </td>
              <td class="acciones">
                <a href="admin_usuarios.php?editar=<?= (int)$u['id'] ?><?= $termino !== '' ? '&buscar=' . urlencode($termino) : '' ?>#form-usuario"
                   class="btn-icon btn-editar" title="Editar">✎</a>

                <form method="POST" action="admin_usuarios.php"
                      style="display:inline;"
                      onsubmit="return confirm('¿Eliminar a <?= e($u['nombre_completo']) ?>?');">
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <input type="hidden" name="buscar" value="<?= e($termino) ?>">
                  <button type="submit" class="btn-icon btn-eliminar"
                          title="Eliminar">🗑</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="lista-footer">
      <span>
        Mostrando
        <?= $total_usuarios === 0 ? 0 : ($pagina_actual - 1) * $por_pagina + 1 ?>
        - <?= min($pagina_actual * $por_pagina, $total_usuarios) ?>
        de <?= $total_usuarios ?> usuarios
      </span>

      <div class="paginacion">
        <a class="pag-btn <?= $pagina_actual <= 1 ? 'disabled' : '' ?>"
           href="admin_usuarios.php?<?= http_build_query(array_filter([
               'buscar' => $termino,
               'pagina' => max(1, $pagina_actual - 1)
           ], fn($v) => $v !== '')) ?>">‹</a>

        <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
          <a class="pag-btn <?= $p === $pagina_actual ? 'activo' : '' ?>"
             href="admin_usuarios.php?<?= http_build_query(array_filter([
                 'buscar' => $termino,
                 'pagina' => $p
             ], fn($v) => $v !== '')) ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>

        <a class="pag-btn <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>"
           href="admin_usuarios.php?<?= http_build_query(array_filter([
               'buscar' => $termino,
               'pagina' => min($total_paginas, $pagina_actual + 1)
           ], fn($v) => $v !== '')) ?>">›</a>
      </div>
    </div>
  </section>
</main>

<script>
function togglePassword() {
  const campo = document.getElementById('campoContrasena');
  campo.type = campo.type === 'password' ? 'text' : 'password';
}

function actualizarCampoRepartidor() {
  const rol = document.getElementById('rolUsuario');
  const contenedor = document.getElementById('campoRepartidor');
  const repartidor = document.getElementById('repartidorUsuario');

  const esTransportista = rol.value === 'Transportista';

  contenedor.style.display = esTransportista ? '' : 'none';
  repartidor.required = esTransportista;

  if (!esTransportista) {
    repartidor.value = '';
  }
}

document.getElementById('rolUsuario')
  .addEventListener('change', actualizarCampoRepartidor);

actualizarCampoRepartidor();
</script>
</body>
</html>
