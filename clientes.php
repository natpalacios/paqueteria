<?php
session_start();

if (!isset($_SESSION['clientes'])) {
    $_SESSION['clientes'] = [
        ['id' => 1,  'nombre_completo' => 'María López',      'identificacion' => '001-000000-0000A', 'telefono' => '8888-8888', 'correo' => 'maria@correo.com',   'direccion' => 'Managua, Nicaragua',   'estado' => 'Activo'],
        ['id' => 2,  'nombre_completo' => 'Carlos Ramírez',   'identificacion' => '002-000000-0000B', 'telefono' => '8777-7777', 'correo' => 'carlos@correo.com',  'direccion' => 'León, Nicaragua',      'estado' => 'Activo'],
        ['id' => 3,  'nombre_completo' => 'Ana Gómez',        'identificacion' => '003-000000-0000C', 'telefono' => '8666-6666', 'correo' => 'ana@correo.com',     'direccion' => 'Chinandega, Nicaragua','estado' => 'Activo'],
        ['id' => 4,  'nombre_completo' => 'Luis Hernández',   'identificacion' => '004-000000-0000D', 'telefono' => '8555-5555', 'correo' => 'luis@correo.com',    'direccion' => 'Estelí, Nicaragua',    'estado' => 'Inactivo'],
        ['id' => 5,  'nombre_completo' => 'Sofía Martínez',   'identificacion' => '005-000000-0000E', 'telefono' => '8444-4444', 'correo' => 'sofia@correo.com',   'direccion' => 'Matagalpa, Nicaragua', 'estado' => 'Activo'],
        ['id' => 6,  'nombre_completo' => 'Jorge Castillo',   'identificacion' => '006-000000-0000F', 'telefono' => '8333-3333', 'correo' => 'jorge@correo.com',   'direccion' => 'Masaya, Nicaragua',    'estado' => 'Activo'],
        ['id' => 7,  'nombre_completo' => 'Patricia Reyes',   'identificacion' => '007-000000-0000G', 'telefono' => '8222-2222', 'correo' => 'patricia@correo.com','direccion' => 'Granada, Nicaragua',   'estado' => 'Activo'],
        ['id' => 8,  'nombre_completo' => 'Roberto Silva',    'identificacion' => '008-000000-0000H', 'telefono' => '8111-1111', 'correo' => 'roberto@correo.com', 'direccion' => 'Rivas, Nicaragua',     'estado' => 'Inactivo'],
        ['id' => 9,  'nombre_completo' => 'Daniela Vargas',   'identificacion' => '009-000000-0000I', 'telefono' => '8999-0000', 'correo' => 'daniela@correo.com', 'direccion' => 'Jinotepe, Nicaragua',  'estado' => 'Activo'],
        ['id' => 10, 'nombre_completo' => 'Fernando Ortega',  'identificacion' => '010-000000-0000J', 'telefono' => '8888-0001', 'correo' => 'fernando@correo.com','direccion' => 'Diriamba, Nicaragua',  'estado' => 'Activo'],
        ['id' => 11, 'nombre_completo' => 'Valeria Campos',   'identificacion' => '011-000000-0000K', 'telefono' => '8777-0002', 'correo' => 'valeria@correo.com', 'direccion' => 'Jinotega, Nicaragua',  'estado' => 'Activo'],
        ['id' => 12, 'nombre_completo' => 'Ricardo Morales',  'identificacion' => '012-000000-0000L', 'telefono' => '8666-0003', 'correo' => 'ricardo@correo.com', 'direccion' => 'Boaco, Nicaragua',     'estado' => 'Activo'],
    ];
    $_SESSION['clientes_siguiente_id'] = 13;
}

$errores = [];
$cliente_editar = null;

function cliente_buscar_por_id($id) {
    foreach ($_SESSION['clientes'] as $c) {
        if ($c['id'] == $id) return $c;
    }
    return null;
}

function identificacion_repetida($identificacion, $id_excluir = null) {
    foreach ($_SESSION['clientes'] as $c) {
        if ($id_excluir !== null && $c['id'] == $id_excluir) continue;
        if ($c['identificacion'] === $identificacion) return true;
    }
    return false;
}

if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];

    $_SESSION['clientes'] = array_values(array_filter(
        $_SESSION['clientes'],
        fn($c) => $c['id'] != $id
    ));

    header('Location: clientes.php?' . http_build_query(array_filter([
        'buscar' => $_GET['buscar'] ?? null,
        'pagina' => $_GET['pagina'] ?? null,
    ])));
    exit;
}

if (isset($_GET['editar'])) {
    $cliente_editar = cliente_buscar_por_id((int) $_GET['editar']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar') {
    $id_edicion      = $_POST['id'] !== '' ? (int) $_POST['id'] : null;
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $identificacion  = trim($_POST['identificacion'] ?? '');
    $telefono        = trim($_POST['telefono'] ?? '');
    $correo          = trim($_POST['correo'] ?? '');
    $direccion       = trim($_POST['direccion'] ?? '');
    $estado          = $_POST['estado'] ?? 'Activo';

    if ($nombre_completo === '') $errores[] = 'El nombre completo es obligatorio.';
    if ($identificacion === '') $errores[] = 'La cédula / identificación es obligatoria.';
    if ($telefono === '') $errores[] = 'El teléfono es obligatorio.';
    if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = 'El correo electrónico no es válido.';
    if (identificacion_repetida($identificacion, $id_edicion)) $errores[] = 'Ya existe un cliente con esa cédula / identificación.';

    if (empty($errores)) {
        if ($id_edicion === null) {
            $_SESSION['clientes'][] = [
                'id' => $_SESSION['clientes_siguiente_id']++,
                'nombre_completo' => $nombre_completo,
                'identificacion' => $identificacion,
                'telefono' => $telefono,
                'correo' => $correo,
                'direccion' => $direccion,
                'estado' => $estado,
            ];
        } else {
            foreach ($_SESSION['clientes'] as &$c) {
                if ($c['id'] == $id_edicion) {
                    $c['nombre_completo'] = $nombre_completo;
                    $c['identificacion'] = $identificacion;
                    $c['telefono'] = $telefono;
                    $c['correo'] = $correo;
                    $c['direccion'] = $direccion;
                    $c['estado'] = $estado;
                }
            }
            unset($c);
        }
        header('Location: clientes.php?' . http_build_query(array_filter([
            'buscar' => $_GET['buscar'] ?? null,
            'pagina' => $_GET['pagina'] ?? null,
        ])));
        exit;
    } else {
        $cliente_editar = [
            'id' => $id_edicion,
            'nombre_completo' => $nombre_completo,
            'identificacion' => $identificacion,
            'telefono' => $telefono,
            'correo' => $correo,
            'direccion' => $direccion,
            'estado' => $estado,
        ];
    }
}

$termino = trim($_GET['buscar'] ?? '');
$clientes_filtrados = $_SESSION['clientes'];
if ($termino !== '') {
    $termino_lower = mb_strtolower($termino);
    $clientes_filtrados = array_values(array_filter($clientes_filtrados, function ($c) use ($termino_lower) {
        return str_contains(mb_strtolower($c['nombre_completo']), $termino_lower)
            || str_contains(mb_strtolower($c['telefono']), $termino_lower)
            || str_contains(mb_strtolower($c['correo']), $termino_lower);
    }));
}

$por_pagina = 5;
$total_clientes = count($clientes_filtrados);
$total_paginas = max(1, (int) ceil($total_clientes / $por_pagina));
$pagina_actual = max(1, min($total_paginas, (int) ($_GET['pagina'] ?? 1)));
$clientes_pagina = array_slice($clientes_filtrados, ($pagina_actual - 1) * $por_pagina, $por_pagina);
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

<nav class="side-nav">
  <a href="#" class="nav-item"><span class="ico">🏠</span> Inicio</a>
  <a href="#" class="nav-item"><span class="ico">👤</span> Usuarios</a>
  <a href="clientes.php" class="nav-item active"><span class="ico">👥</span> Clientes</a>
  <a href="#" class="nav-item"><span class="ico">📦</span> Paquetes</a>
  <a href="#" class="nav-item"><span class="ico">📍</span> Seguimiento</a>
  <a href="#" class="nav-item"><span class="ico">🧑‍💼</span> Empleados</a>
  <a href="#" class="nav-item"><span class="ico">📄</span> Reportes</a>
  <div class="nav-spacer"></div>
  <a href="#" class="nav-item nav-logout"><span class="ico">⏻</span> Cerrar sesión</a>
</nav>

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
      <ul><?php foreach ($errores as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
    </div>
  <?php endif; ?>

  <section class="panel" id="form-cliente">
    <div class="panel-title">
      <span class="plus">⊕</span>
      <?= $cliente_editar && !empty($cliente_editar['id']) ? 'Editar cliente' : 'Nuevo cliente' ?>
    </div>

    <form method="POST" action="clientes.php<?= $termino !== '' ? '?buscar=' . urlencode($termino) : '' ?>">
      <input type="hidden" name="accion" value="guardar">
      <input type="hidden" name="id" value="<?= htmlspecialchars($cliente_editar['id'] ?? '') ?>">

      <div class="form-grid">
        <div class="campo">
          <label>Nombre completo *</label>
          <div class="campo-icono">
            <span class="icono">👤</span>
            <input type="text" name="nombre_completo" placeholder="Ej. María López" value="<?= htmlspecialchars($cliente_editar['nombre_completo'] ?? '') ?>" required>
          </div>
        </div>
        <div class="campo">
          <label>Cédula / Identificación *</label>
          <div class="campo-icono">
            <span class="icono">🪪</span>
            <input type="text" name="identificacion" placeholder="Ej. 001-000000-0000A" value="<?= htmlspecialchars($cliente_editar['identificacion'] ?? '') ?>" required>
          </div>
        </div>
        <div class="campo">
          <label>Teléfono *</label>
          <div class="campo-icono">
            <span class="icono">📞</span>
            <input type="text" name="telefono" placeholder="Ej. 8888-8888" value="<?= htmlspecialchars($cliente_editar['telefono'] ?? '') ?>" required>
          </div>
        </div>

        <div class="campo">
          <label>Correo electrónico</label>
          <div class="campo-icono">
            <span class="icono">✉️</span>
            <input type="email" name="correo" placeholder="Ej. maria@correo.com" value="<?= htmlspecialchars($cliente_editar['correo'] ?? '') ?>">
          </div>
        </div>
        <div class="campo">
          <label>Dirección</label>
          <div class="campo-icono">
            <span class="icono">📍</span>
            <input type="text" name="direccion" placeholder="Ej. Managua, Nicaragua" value="<?= htmlspecialchars($cliente_editar['direccion'] ?? '') ?>">
          </div>
        </div>
        <div class="campo">
          <label>Estado</label>
          <div class="campo-icono">
            <span class="icono">✅</span>
            <select name="estado">
              <option value="Activo" <?= (($cliente_editar['estado'] ?? 'Activo') === 'Activo') ? 'selected' : '' ?>>Activo</option>
              <option value="Inactivo" <?= (($cliente_editar['estado'] ?? '') === 'Inactivo') ? 'selected' : '' ?>>Inactivo</option>
            </select>
          </div>
        </div>
      </div>

      <div class="form-acciones">
        <a href="clientes.php<?= $termino !== '' ? '?buscar=' . urlencode($termino) : '' ?>" class="btn-outline">↻ Limpiar</a>
        <button type="submit" class="btn-primary">💾 Guardar</button>
      </div>
    </form>
  </section>

  <section class="panel">
    <div class="lista-header">
      <span class="lista-icono">👥</span>
      <h2>Lista de clientes</h2>
      <form method="GET" action="clientes.php" class="buscador">
        <input type="text" name="buscar" placeholder="🔍 Buscar por nombre, teléfono o correo..." value="<?= htmlspecialchars($termino) ?>">
      </form>
      <a href="#form-cliente" class="btn-primary">+ Nuevo cliente</a>
    </div>

    <div class="table-scroll">
      <table class="tabla-datos">
        <thead>
          <tr>
            <th>#</th><th>Cliente</th><th>Identificación</th><th>Teléfono</th>
            <th>Correo</th><th>Dirección</th><th>Estado</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($clientes_pagina)): ?>
            <tr><td colspan="8" class="sin-resultados">No se encontraron clientes.</td></tr>
          <?php endif; ?>
          <?php foreach ($clientes_pagina as $i => $c): ?>
            <tr>
              <td data-label="#"><?= ($pagina_actual - 1) * $por_pagina + $i + 1 ?></td>
              <td data-label="Cliente"><?= htmlspecialchars($c['nombre_completo']) ?></td>
              <td data-label="Identificación"><?= htmlspecialchars($c['identificacion']) ?></td>
              <td data-label="Teléfono"><?= htmlspecialchars($c['telefono']) ?></td>
              <td data-label="Correo"><?= htmlspecialchars($c['correo']) ?></td>
              <td data-label="Dirección"><?= htmlspecialchars($c['direccion']) ?></td>
              <td data-label="Estado"><span class="badge-estado <?= $c['estado'] === 'Activo' ? 'estado-activo' : 'estado-inactivo' ?>"><?= $c['estado'] === 'Activo' ? '✓ ' : '✕ ' ?><?= $c['estado'] ?></span></td>
              <td data-label="" class="acciones">
                <a href="clientes.php?editar=<?= $c['id'] ?>#form-cliente" class="btn-icon btn-editar" title="Editar">✎</a>
                <a href="clientes.php?eliminar=<?= $c['id'] ?>&buscar=<?= urlencode($termino) ?>&pagina=<?= $pagina_actual ?>"
                   class="btn-icon btn-eliminar" title="Eliminar"
                   onclick="return confirm('¿Eliminar a <?= htmlspecialchars(addslashes($c['nombre_completo'])) ?>?');">🗑</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="lista-footer">
      <span>Mostrando <?= $total_clientes === 0 ? 0 : ($pagina_actual - 1) * $por_pagina + 1 ?> - <?= min($pagina_actual * $por_pagina, $total_clientes) ?> de <?= $total_clientes ?> clientes</span>
      <div class="paginacion">
        <a class="pag-btn <?= $pagina_actual <= 1 ? 'disabled' : '' ?>" href="clientes.php?<?= http_build_query(array_filter(['buscar' => $termino, 'pagina' => $pagina_actual - 1])) ?>">‹</a>
        <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
          <a class="pag-btn <?= $p === $pagina_actual ? 'activo' : '' ?>" href="clientes.php?<?= http_build_query(array_filter(['buscar' => $termino, 'pagina' => $p])) ?>"><?= $p ?></a>
        <?php endfor; ?>
        <a class="pag-btn <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>" href="clientes.php?<?= http_build_query(array_filter(['buscar' => $termino, 'pagina' => $pagina_actual + 1])) ?>">›</a>
      </div>
    </div>
  </section>
</main>

</body>
</html>
