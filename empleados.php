<?php
session_start();

if (!isset($_SESSION['empleados'])) {
    $_SESSION['empleados'] = [
        ['id' => 1, 'nombre_completo' => 'Luis Torres',    'cedula' => '001-000000-0000B', 'telefono' => '8888-8888', 'correo' => 'ltorres@empresa.com', 'cargo' => 'Transportista',       'sucursal' => 'Managua',    'estado' => 'Activo'],
        ['id' => 2, 'nombre_completo' => 'Ana Gómez',      'cedula' => '002-000000-0000C', 'telefono' => '8777-7777', 'correo' => 'agomez@empresa.com',  'cargo' => 'Supervisor',         'sucursal' => 'Chinandega', 'estado' => 'Activo'],
        ['id' => 3, 'nombre_completo' => 'Carlos Ramírez', 'cedula' => '003-000000-0000D', 'telefono' => '8666-6666', 'correo' => 'cramirez@empresa.com','cargo' => 'Repartidor',        'sucursal' => 'León',       'estado' => 'Activo'],
        ['id' => 4, 'nombre_completo' => 'José Pérez',     'cedula' => '004-000000-0000E', 'telefono' => '8555-5555', 'correo' => 'jperez@empresa.com',  'cargo' => 'Administrativo',     'sucursal' => 'Estelí',     'estado' => 'Inactivo'],
        ['id' => 5, 'nombre_completo' => 'María López',    'cedula' => '005-000000-0000F', 'telefono' => '8444-4444', 'correo' => 'mlopez@empresa.com',  'cargo' => 'Atención al cliente','sucursal' => 'Matagalpa', 'estado' => 'Activo'],
    ];
    $_SESSION['empleados_siguiente_id'] = 6;
}

$cargos = ['Transportista', 'Supervisor', 'Repartidor', 'Administrativo', 'Atención al cliente'];
$sucursales = ['Managua', 'León', 'Chinandega', 'Estelí', 'Matagalpa', 'Masaya', 'Granada'];
$errores = [];
$empleado_editar = null;

function empleado_buscar_por_id($id) {
    foreach ($_SESSION['empleados'] as $e) {
        if ($e['id'] == $id) return $e;
    }
    return null;
}

function cedula_repetida($cedula, $id_excluir = null) {
    foreach ($_SESSION['empleados'] as $e) {
        if ($id_excluir !== null && $e['id'] == $id_excluir) continue;
        if ($e['cedula'] === $cedula) return true;
    }
    return false;
}

if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];

    $_SESSION['empleados'] = array_values(array_filter(
        $_SESSION['empleados'],
        fn($e) => $e['id'] != $id
    ));

    header('Location: empleados.php?' . http_build_query(array_filter([
        'buscar' => $_GET['buscar'] ?? null,
        'pagina' => $_GET['pagina'] ?? null,
    ])));
    exit;
}

if (isset($_GET['editar'])) {
    $empleado_editar = empleado_buscar_por_id((int) $_GET['editar']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar') {
    $id_edicion      = $_POST['id'] !== '' ? (int) $_POST['id'] : null;
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $cedula          = trim($_POST['cedula'] ?? '');
    $telefono        = trim($_POST['telefono'] ?? '');
    $correo          = trim($_POST['correo'] ?? '');
    $cargo           = $_POST['cargo'] ?? '';
    $sucursal        = $_POST['sucursal'] ?? '';
    $estado          = $_POST['estado'] ?? 'Activo';

    if ($nombre_completo === '') $errores[] = 'El nombre completo es obligatorio.';
    if ($cedula === '') $errores[] = 'La cédula es obligatoria.';
    if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = 'El correo electrónico no es válido.';
    if (!in_array($cargo, $cargos, true)) $errores[] = 'Debes seleccionar un cargo.';
    if (!in_array($sucursal, $sucursales, true)) $errores[] = 'Debes seleccionar una sucursal.';
    if (cedula_repetida($cedula, $id_edicion)) $errores[] = 'Ya existe un empleado con esa cédula.';

    if (empty($errores)) {
        if ($id_edicion === null) {
            $_SESSION['empleados'][] = [
                'id' => $_SESSION['empleados_siguiente_id']++,
                'nombre_completo' => $nombre_completo,
                'cedula' => $cedula,
                'telefono' => $telefono,
                'correo' => $correo,
                'cargo' => $cargo,
                'sucursal' => $sucursal,
                'estado' => $estado,
            ];
        } else {
            foreach ($_SESSION['empleados'] as &$e) {
                if ($e['id'] == $id_edicion) {
                    $e['nombre_completo'] = $nombre_completo;
                    $e['cedula'] = $cedula;
                    $e['telefono'] = $telefono;
                    $e['correo'] = $correo;
                    $e['cargo'] = $cargo;
                    $e['sucursal'] = $sucursal;
                    $e['estado'] = $estado;
                }
            }
            unset($e);
        }
        header('Location: empleados.php?' . http_build_query(array_filter([
            'buscar' => $_GET['buscar'] ?? null,
            'pagina' => $_GET['pagina'] ?? null,
        ])));
        exit;
    } else {
        $empleado_editar = [
            'id' => $id_edicion,
            'nombre_completo' => $nombre_completo,
            'cedula' => $cedula,
            'telefono' => $telefono,
            'correo' => $correo,
            'cargo' => $cargo,
            'sucursal' => $sucursal,
            'estado' => $estado,
        ];
    }
}

$termino = trim($_GET['buscar'] ?? '');
$empleados_filtrados = $_SESSION['empleados'];
if ($termino !== '') {
    $termino_lower = mb_strtolower($termino);
    $empleados_filtrados = array_values(array_filter($empleados_filtrados, function ($e) use ($termino_lower) {
        return str_contains(mb_strtolower($e['nombre_completo']), $termino_lower)
            || str_contains(mb_strtolower($e['cedula']), $termino_lower)
            || str_contains(mb_strtolower($e['cargo']), $termino_lower)
            || str_contains(mb_strtolower($e['sucursal']), $termino_lower);
    }));
}

$por_pagina = 5;
$total_registros = count($empleados_filtrados);
$total_paginas = max(1, (int) ceil($total_registros / $por_pagina));
$pagina_actual = max(1, min($total_paginas, (int) ($_GET['pagina'] ?? 1)));
$empleados_pagina = array_slice($empleados_filtrados, ($pagina_actual - 1) * $por_pagina, $por_pagina);

$total_empleados = count($_SESSION['empleados']);
$total_activos = count(array_filter($_SESSION['empleados'], fn($e) => $e['estado'] === 'Activo'));
$total_inactivos = $total_empleados - $total_activos;
$cargos_diferentes = count(array_unique(array_column($_SESSION['empleados'], 'cargo')));

$cargo_clase = [
    'Transportista' => 'cargo-transportista',
    'Supervisor' => 'cargo-supervisor',
    'Repartidor' => 'cargo-repartidor',
    'Administrativo' => 'cargo-administrativo',
    'Atención al cliente' => 'cargo-atencion',
];
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
      <a href="admin_usuarios.php" class="menu-link"><span>👤</span><span>Usuario</span></a>
      <a href="clientes.php" class="menu-link"><span>👥</span><span>Clientes</span></a>
      <a href="empleados.php" class="menu-link active"><span>🧑‍💼</span><span>Empleado</span></a>
      <a href="incidencia.php" class="menu-link"><span>⚠️</span><span>Incidencia</span></a>
      <a href="reporte.php" class="menu-link"><span>📊</span><span>Reporte</span></a>
      <a href="logout.php" class="menu-link"><span>↪</span><span>Cerrar sesión</span></a>
    </div>
  </div>
</aside>

<script>
(function(){
  var menu = document.getElementById('sideMenu');
  var fondo = document.getElementById('menuFondo');
  function abrir(){ menu.classList.add('activo'); fondo.classList.add('activo'); }
  function cerrar(){ menu.classList.remove('activo'); fondo.classList.remove('activo'); }
  document.getElementById('menuBtn').addEventListener('click', abrir);
  document.getElementById('menuClose').addEventListener('click', cerrar);
  fondo.addEventListener('click', cerrar);
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') cerrar(); });
})();
</script>

<main>
  <header class="page-head">
    <span class="page-icon">👨‍👩‍👧</span>
    <div>
      <h1>Empleados</h1>
      <p>Gestiona la información, cargos y estado de los empleados del sistema.</p>
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
      <ul><?php foreach ($errores as $er) echo '<li>' . htmlspecialchars($er) . '</li>'; ?></ul>
    </div>
  <?php endif; ?>

  <section class="panel-split" id="form-empleado">
    <div class="panel panel-form">
      <div class="panel-title panel-title-oscuro">
        <span class="plus">⊕</span>
        <?= $empleado_editar && !empty($empleado_editar['id']) ? 'Editar empleado' : 'Registrar empleado' ?>
      </div>

      <form method="POST" action="empleados.php<?= $termino !== '' ? '?buscar=' . urlencode($termino) : '' ?>">
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id" value="<?= htmlspecialchars($empleado_editar['id'] ?? '') ?>">

        <div class="form-grid">
          <div class="campo">
            <label>Nombre completo *</label>
            <div class="campo-icono">
              <span class="icono">👤</span>
              <input type="text" name="nombre_completo" placeholder="Ej. Luis Torres" value="<?= htmlspecialchars($empleado_editar['nombre_completo'] ?? '') ?>" required>
            </div>
          </div>
          <div class="campo">
            <label>Cédula *</label>
            <div class="campo-icono">
              <span class="icono">🪪</span>
              <input type="text" name="cedula" placeholder="Ej. 001-000000-0000A" value="<?= htmlspecialchars($empleado_editar['cedula'] ?? '') ?>" required>
            </div>
          </div>
          <div class="campo">
            <label>Teléfono</label>
            <div class="campo-icono">
              <span class="icono">📞</span>
              <input type="text" name="telefono" placeholder="Ej. 8888-8888" value="<?= htmlspecialchars($empleado_editar['telefono'] ?? '') ?>">
            </div>
          </div>

          <div class="campo">
            <label>Correo electrónico</label>
            <div class="campo-icono">
              <span class="icono">✉️</span>
              <input type="email" name="correo" placeholder="Ej. ltorres@empresa.com" value="<?= htmlspecialchars($empleado_editar['correo'] ?? '') ?>">
            </div>
          </div>
          <div class="campo">
            <label>Cargo *</label>
            <div class="campo-icono">
              <span class="icono">💼</span>
              <select name="cargo" required>
                <option value="" disabled <?= empty($empleado_editar['cargo']) ? 'selected' : '' ?>>Seleccionar cargo</option>
                <?php foreach ($cargos as $c): ?>
                  <option value="<?= $c ?>" <?= (($empleado_editar['cargo'] ?? '') === $c) ? 'selected' : '' ?>><?= $c ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="campo">
            <label>Sucursal *</label>
            <div class="campo-icono">
              <span class="icono">🏢</span>
              <select name="sucursal" required>
                <option value="" disabled <?= empty($empleado_editar['sucursal']) ? 'selected' : '' ?>>Seleccionar sucursal</option>
                <?php foreach ($sucursales as $s): ?>
                  <option value="<?= $s ?>" <?= (($empleado_editar['sucursal'] ?? '') === $s) ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="campo">
            <label>Estado</label>
            <div class="campo-icono">
              <span class="icono">✅</span>
              <select name="estado">
                <option value="Activo" <?= (($empleado_editar['estado'] ?? 'Activo') === 'Activo') ? 'selected' : '' ?>>Activo</option>
                <option value="Inactivo" <?= (($empleado_editar['estado'] ?? '') === 'Inactivo') ? 'selected' : '' ?>>Inactivo</option>
              </select>
            </div>
          </div>
        </div>

        <div class="form-acciones">
          <a href="empleados.php<?= $termino !== '' ? '?buscar=' . urlencode($termino) : '' ?>" class="btn-outline">↻ Limpiar</a>
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
        <input type="text" name="buscar" placeholder="🔍 Buscar por nombre, cédula, cargo o sucursal..." value="<?= htmlspecialchars($termino) ?>">
      </form>
      <a href="#form-empleado" class="btn-primary">+ Nuevo empleado</a>
    </div>

    <div class="table-scroll">
      <table class="tabla-datos">
        <thead>
          <tr>
            <th>#</th><th>Nombre</th><th>Cédula</th><th>Cargo</th>
            <th>Teléfono</th><th>Sucursal</th><th>Estado</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($empleados_pagina)): ?>
            <tr><td colspan="8" class="sin-resultados">No se encontraron empleados.</td></tr>
          <?php endif; ?>
          <?php foreach ($empleados_pagina as $i => $e): ?>
            <tr>
              <td data-label="#"><?= ($pagina_actual - 1) * $por_pagina + $i + 1 ?></td>
              <td data-label="Nombre"><?= htmlspecialchars($e['nombre_completo']) ?></td>
              <td data-label="Cédula"><?= htmlspecialchars($e['cedula']) ?></td>
              <td data-label="Cargo"><span class="badge-cargo <?= $cargo_clase[$e['cargo']] ?? '' ?>"><?= htmlspecialchars($e['cargo']) ?></span></td>
              <td data-label="Teléfono"><?= htmlspecialchars($e['telefono']) ?></td>
              <td data-label="Sucursal"><?= htmlspecialchars($e['sucursal']) ?></td>
              <td data-label="Estado"><span class="badge-estado <?= $e['estado'] === 'Activo' ? 'estado-activo' : 'estado-inactivo' ?>"><?= $e['estado'] === 'Activo' ? '✓ ' : '✕ ' ?><?= $e['estado'] ?></span></td>
              <td data-label="" class="acciones">
                <a href="empleados.php?editar=<?= $e['id'] ?>#form-empleado" class="btn-icon btn-editar" title="Editar">✎</a>
                <a href="empleados.php?eliminar=<?= $e['id'] ?>&buscar=<?= urlencode($termino) ?>&pagina=<?= $pagina_actual ?>"
                   class="btn-icon btn-eliminar" title="Eliminar"
                   onclick="return confirm('¿Eliminar a <?= htmlspecialchars(addslashes($e['nombre_completo'])) ?>?');">🗑</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="lista-footer">
      <span>Mostrando <?= $total_registros === 0 ? 0 : ($pagina_actual - 1) * $por_pagina + 1 ?> - <?= min($pagina_actual * $por_pagina, $total_registros) ?> de <?= $total_registros ?> empleados</span>
      <div class="paginacion">
        <a class="pag-btn <?= $pagina_actual <= 1 ? 'disabled' : '' ?>" href="empleados.php?<?= http_build_query(array_filter(['buscar' => $termino, 'pagina' => $pagina_actual - 1])) ?>">‹</a>
        <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
          <a class="pag-btn <?= $p === $pagina_actual ? 'activo' : '' ?>" href="empleados.php?<?= http_build_query(array_filter(['buscar' => $termino, 'pagina' => $p])) ?>"><?= $p ?></a>
        <?php endfor; ?>
        <a class="pag-btn <?= $pagina_actual >= $total_paginas ? 'disabled' : '' ?>" href="empleados.php?<?= http_build_query(array_filter(['buscar' => $termino, 'pagina' => $pagina_actual + 1])) ?>">›</a>
      </div>
    </div>
  </section>
</main>

</body>
</html> 
