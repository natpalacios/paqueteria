
<?php
session_start();
require_once __DIR__ . '/conexion.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$errores = [];
$cliente_editar = null;

function h($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function volverClientes($buscar = '', $pagina = 1) {
    $parametros = [];
    if ($buscar !== '') $parametros['buscar'] = $buscar;
    if ($pagina > 1) $parametros['pagina'] = $pagina;

    header('Location: clientes.php' .
        (!empty($parametros) ? '?' . http_build_query($parametros) : ''));
    exit;
}

// Datos para conservar la búsqueda y la página
$termino = trim($_GET['buscar'] ?? $_POST['buscar'] ?? '');
$pagina_recibida = max(1, (int)($_GET['pagina'] ?? $_POST['pagina'] ?? 1));

// Eliminar cliente
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];

    if ($id > 0) {
        $stmt = $conexion->prepare("DELETE FROM clientes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    volverClientes($termino, $pagina_recibida);
}

// Guardar o actualizar cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['accion'] ?? '') === 'guardar') {

    $id_edicion = trim($_POST['id'] ?? '') !== ''
        ? (int)$_POST['id']
        : null;

    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $identificacion  = trim($_POST['identificacion'] ?? '');
    $telefono        = trim($_POST['telefono'] ?? '');
    $correo          = trim($_POST['correo'] ?? '');
    $direccion       = trim($_POST['direccion'] ?? '');
    $estado          = $_POST['estado'] ?? 'Activo';

    if ($nombre_completo === '') {
        $errores[] = 'El nombre completo es obligatorio.';
    }

    if ($identificacion === '') {
        $errores[] = 'La cédula / identificación es obligatoria.';
    }

    if (strlen($nombre_completo) > 100) {
        $errores[] = 'El nombre no puede superar los 100 caracteres.';
    }

    if (strlen($identificacion) > 30) {
        $errores[] = 'La identificación no puede superar los 30 caracteres.';
    }

    if (strlen($telefono) > 20) {
        $errores[] = 'El teléfono no puede superar los 20 caracteres.';
    }

    if (strlen($correo) > 100) {
        $errores[] = 'El correo no puede superar los 100 caracteres.';
    }

    if (strlen($direccion) > 255) {
        $errores[] = 'La dirección no puede superar los 255 caracteres.';
    }

    if ($telefono === '') {
        $errores[] = 'El teléfono es obligatorio.';
    }

    if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no es válido.';
    }

    if (!in_array($estado, ['Activo', 'Inactivo'], true)) {
        $errores[] = 'El estado seleccionado no es válido.';
    }

    // Comprobar que la cédula no pertenezca a otro cliente
    if ($identificacion !== '') {
        if ($id_edicion !== null) {
            $stmt = $conexion->prepare(
                "SELECT id FROM clientes WHERE cedula = ? AND id <> ? LIMIT 1"
            );
            $stmt->bind_param("si", $identificacion, $id_edicion);
        } else {
            $stmt = $conexion->prepare(
                "SELECT id FROM clientes WHERE cedula = ? LIMIT 1"
            );
            $stmt->bind_param("s", $identificacion);
        }

        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errores[] = 'Ya existe un cliente con esa cédula / identificación.';
        }

        $stmt->close();
    }

    if (empty($errores)) {
        if ($id_edicion === null) {
            $stmt = $conexion->prepare(
                "INSERT INTO clientes
                (nombre, cedula, telefono, correo, direccion, estado)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssssss",
                $nombre_completo,
                $identificacion,
                $telefono,
                $correo,
                $direccion,
                $estado
            );
        } else {
            $stmt = $conexion->prepare(
                "UPDATE clientes
                 SET nombre = ?, cedula = ?, telefono = ?,
                     correo = ?, direccion = ?, estado = ?
                 WHERE id = ?"
            );

            $stmt->bind_param(
                "ssssssi",
                $nombre_completo,
                $identificacion,
                $telefono,
                $correo,
                $direccion,
                $estado,
                $id_edicion
            );
        }

        $stmt->execute();
        $stmt->close();

        volverClientes($termino, $pagina_recibida);
    } else {
        $cliente_editar = [
            'id' => $id_edicion,
            'nombre_completo' => $nombre_completo,
            'identificacion' => $identificacion,
            'telefono' => $telefono,
            'correo' => $correo,
            'direccion' => $direccion,
            'estado' => $estado
        ];
    }
}

// Cargar cliente para editar
if ($cliente_editar === null && isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];

    if ($id > 0) {
        $stmt = $conexion->prepare(
            "SELECT id, nombre, cedula, telefono, correo, direccion, estado
             FROM clientes WHERE id = ? LIMIT 1"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result(
            $id_db,
            $nombre_db,
            $cedula_db,
            $telefono_db,
            $correo_db,
            $direccion_db,
            $estado_db
        );

        if ($stmt->fetch()) {
            $cliente_editar = [
                'id' => $id_db,
                'nombre_completo' => $nombre_db,
                'identificacion' => $cedula_db,
                'telefono' => $telefono_db,
                'correo' => $correo_db,
                'direccion' => $direccion_db,
                'estado' => $estado_db
            ];
        }

        $stmt->close();
    }
}

// Búsqueda en la base de datos
$por_pagina = 5;
$buscar_sql = '%' . $termino . '%';

$stmt = $conexion->prepare(
    "SELECT COUNT(*)
     FROM clientes
     WHERE nombre LIKE ?
        OR cedula LIKE ?
        OR telefono LIKE ?
        OR correo LIKE ?"
);

$stmt->bind_param(
    "ssss",
    $buscar_sql,
    $buscar_sql,
    $buscar_sql,
    $buscar_sql
);

$stmt->execute();
$stmt->bind_result($total_clientes);
$stmt->fetch();
$stmt->close();

$total_clientes = (int)$total_clientes;
$total_paginas = max(1, (int)ceil($total_clientes / $por_pagina));

$pagina_actual = min($total_paginas, $pagina_recibida);
$inicio = ($pagina_actual - 1) * $por_pagina;

// Consultar los clientes de la página actual
$stmt = $conexion->prepare(
    "SELECT id, nombre, cedula, telefono, correo, direccion, estado
     FROM clientes
     WHERE nombre LIKE ?
        OR cedula LIKE ?
        OR telefono LIKE ?
        OR correo LIKE ?
     ORDER BY id DESC
     LIMIT ? OFFSET ?"
);

$stmt->bind_param(
    "ssssii",
    $buscar_sql,
    $buscar_sql,
    $buscar_sql,
    $buscar_sql,
    $por_pagina,
    $inicio
);

$stmt->execute();
$stmt->bind_result(
    $id_db,
    $nombre_db,
    $cedula_db,
    $telefono_db,
    $correo_db,
    $direccion_db,
    $estado_db
);

$clientes_pagina = [];

while ($stmt->fetch()) {
    $clientes_pagina[] = [
        'id' => $id_db,
        'nombre_completo' => $nombre_db,
        'identificacion' => $cedula_db,
        'telefono' => $telefono_db,
        'correo' => $correo_db,
        'direccion' => $direccion_db,
        'estado' => $estado_db
    ];
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Clientes · Grupo S.A Logistic</title>
<link rel="stylesheet" href="css/cliente.css">
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
(function(){
  var menu = document.getElementById('sideMenu');
  var fondo = document.getElementById('menuFondo');

  function abrir(){
    menu.classList.add('activo');
    fondo.classList.add('activo');
  }

  function cerrar(){
    menu.classList.remove('activo');
    fondo.classList.remove('activo');
  }

  document.getElementById('menuBtn').addEventListener('click', abrir);
  document.getElementById('menuClose').addEventListener('click', cerrar);
  fondo.addEventListener('click', cerrar);

  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') cerrar();
  });
})();
</script>

<main>
  <header class="page-head">
    <span class="page-icon">👥</span>
    <div>
      <h1>Clientes</h1>
      <p>Registre, consulte y gestione la información de los clientes del sistema.</p>
    </div>
  </header>

  <?php if (!empty($errores)): ?>
    <div class="alerta-error">
      <ul>
        <?php foreach ($errores as $e): ?>
          <li><?= h($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <section class="panel" id="form-cliente">
    <div class="panel-title">
      <span class="plus">⊕</span>
      <?= $cliente_editar && !empty($cliente_editar['id'])
          ? 'Editar cliente'
          : 'Nuevo cliente' ?>
    </div>

    <form method="POST" action="clientes.php">
      <input type="hidden" name="accion" value="guardar">
      <input type="hidden" name="id" value="<?= h($cliente_editar['id'] ?? '') ?>">
      <input type="hidden" name="buscar" value="<?= h($termino) ?>">
      <input type="hidden" name="pagina" value="<?= $pagina_actual ?>">

      <div class="form-grid">
        <div class="campo">
          <label>Nombre completo *</label>
          <div class="campo-icono">
            <span class="icono">👤</span>
            <input type="text" name="nombre_completo"
              placeholder="Ej. María López"
              maxlength="100"
              value="<?= h($cliente_editar['nombre_completo'] ?? '') ?>"
              required>
          </div>
        </div>

        <div class="campo">
          <label>Cédula / Identificación *</label>
          <div class="campo-icono">
            <span class="icono">🪪</span>
            <input type="text" name="identificacion"
              placeholder="Ej. 001-000000-0000A"
              maxlength="30"
              value="<?= h($cliente_editar['identificacion'] ?? '') ?>"
              required>
          </div>
        </div>

        <div class="campo">
          <label>Teléfono *</label>
          <div class="campo-icono">
            <span class="icono">📞</span>
            <input type="text" name="telefono"
              placeholder="Ej. 8888-8888"
              maxlength="20"
              value="<?= h($cliente_editar['telefono'] ?? '') ?>"
              required>
          </div>
        </div>

        <div class="campo">
          <label>Correo electrónico</label>
          <div class="campo-icono">
            <span class="icono">✉️</span>
            <input type="email" name="correo"
              placeholder="Ej. maria@correo.com"
              maxlength="100"
              value="<?= h($cliente_editar['correo'] ?? '') ?>">
          </div>
        </div>

        <div class="campo">
          <label>Dirección</label>
          <div class="campo-icono">
            <span class="icono">📍</span>
            <input type="text" name="direccion"
              placeholder="Ej. Managua, Nicaragua"
              maxlength="255"
              value="<?= h($cliente_editar['direccion'] ?? '') ?>">
          </div>
        </div>

        <div class="campo">
          <label>Estado</label>
          <div class="campo-icono">
            <span class="icono">✅</span>
            <select name="estado">
              <option value="Activo"
                <?= (($cliente_editar['estado'] ?? 'Activo') === 'Activo')
                    ? 'selected' : '' ?>>Activo</option>
              <option value="Inactivo"
                <?= (($cliente_editar['estado'] ?? '') === 'Inactivo')
                    ? 'selected' : '' ?>>Inactivo</option>
            </select>
          </div>
        </div>
      </div>

      <div class="form-acciones">
        <a href="clientes.php<?= $termino !== ''
            ? '?buscar=' . urlencode($termino)
            : '' ?>" class="btn-outline">↻ Limpiar</a>
        <button type="submit" class="btn-primary">💾 Guardar</button>
      </div>
    </form>
  </section>

  <section class="panel">
    <div class="lista-header">
      <span class="lista-icono">👥</span>
      <h2>Lista de clientes</h2>

      <form method="GET" action="clientes.php" class="buscador">
        <input type="text" name="buscar"
          placeholder="🔍 Buscar por nombre, identificación, teléfono o correo..."
          value="<?= h($termino) ?>">
      </form>

      <a href="clientes.php#form-cliente" class="btn-primary">+ Nuevo cliente</a>
    </div>

    <div class="table-scroll">
      <table class="tabla-datos">
        <thead>
          <tr>
            <th>#</th>
            <th>Cliente</th>
            <th>Identificación</th>
            <th>Teléfono</th>
            <th>Correo</th>
            <th>Dirección</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>

        <tbody>
          <?php if (empty($clientes_pagina)): ?>
            <tr>
              <td colspan="8" class="sin-resultados">
                No se encontraron clientes.
              </td>
            </tr>
          <?php endif; ?>

          <?php foreach ($clientes_pagina as $i => $c): ?>
            <tr>
              <td data-label="#">
                <?= $inicio + $i + 1 ?>
              </td>

              <td data-label="Cliente">
                <?= h($c['nombre_completo']) ?>
              </td>

              <td data-label="Identificación">
                <?= h($c['identificacion']) ?>
              </td>

              <td data-label="Teléfono">
                <?= h($c['telefono']) ?>
              </td>

              <td data-label="Correo">
                <?= h($c['correo']) ?>
              </td>

              <td data-label="Dirección">
                <?= h($c['direccion']) ?>
              </td>

              <td data-label="Estado">
                <span class="badge-estado <?= $c['estado'] === 'Activo'
                    ? 'estado-activo'
                    : 'estado-inactivo' ?>">
                  <?= $c['estado'] === 'Activo' ? '✓ ' : '✕ ' ?>
                  <?= h($c['estado']) ?>
                </span>
              </td>

              <td data-label="Acciones" class="acciones">
                <a href="clientes.php?editar=<?= (int)$c['id'] ?>&buscar=<?= urlencode($termino) ?>&pagina=<?= $pagina_actual ?>#form-cliente"
                   class="btn-icon btn-editar" title="Editar">✎</a>

                <a href="clientes.php?eliminar=<?= (int)$c['id'] ?>&buscar=<?= urlencode($termino) ?>&pagina=<?= $pagina_actual ?>"
                   class="btn-icon btn-eliminar" title="Eliminar"
                   onclick="return confirm('¿Eliminar a <?= h($c['nombre_completo']) ?>? Esta acción no se puede deshacer.');">🗑</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="lista-footer">
      <span>
        Mostrando
        <?= $total_clientes === 0 ? 0 : $inicio + 1 ?>
        -
        <?= min($inicio + $por_pagina, $total_clientes) ?>
        de <?= $total_clientes ?> clientes
      </span>

      <div class="paginacion">
        <a class="pag-btn <?= $pagina_actual <= 1 ? 'disabled' : '' ?>"
           href="clientes.php?<?= http_build_query([
               'buscar' => $termino,
               'pagina' => max(1, $pagina_actual - 1)
           ]) ?>">‹</a>

        <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
          <a class="pag-btn <?= $p === $pagina_actual ? 'activo' : '' ?>"
             href="clientes.php?<?= http_build_query([
                 'buscar' => $termino,
                 'pagina' => $p
             ]) ?>"><?= $p ?></a>
        <?php endfor; ?>

        <a class="pag-btn <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>"
           href="clientes.php?<?= http_build_query([
               'buscar' => $termino,
               'pagina' => min($total_paginas, $pagina_actual + 1)
           ]) ?>">›</a>
      </div>
    </div>
  </section>
</main>

</body>
</html>
